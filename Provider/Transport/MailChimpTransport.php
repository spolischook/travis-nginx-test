<?php

namespace OroCRM\Bundle\MailChimpBundle\Provider\Transport;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

use OroCRM\Bundle\MailChimpBundle\Entity\Member;
use OroCRM\Bundle\MailChimpBundle\Entity\Template;
use OroCRM\Bundle\MailChimpBundle\Exception\RequiredOptionException;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\CampaignIterator;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\ListIterator;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\MemberActivityIterator;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\MemberIterator;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\StaticSegmentListIterator;
use OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator\TemplateIterator;

/**
 * @link http://apidocs.mailchimp.com/api/2.0/
 * @link https://bitbucket.org/mailchimp/mailchimp-api-php/
 */
class MailChimpTransport implements TransportInterface
{
    /**#@+
     * @const string Constants related to datetime representation in MailChimp
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DATE_FORMAT = 'Y-m-d';
    const TIME_FORMAT = 'H:i:s';
    const TIMEZONE = 'UTC';
    /**#@-*/

    /**
     * @var MailChimpClient
     */
    protected $client;

    /**
     * @var MailChimpClientFactory
     */
    protected $mailChimpClientFactory;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param MailChimpClientFactory $mailChimpClientFactory
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(MailChimpClientFactory $mailChimpClientFactory, ManagerRegistry $managerRegistry)
    {
        $this->mailChimpClientFactory = $mailChimpClientFactory;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $apiKey = $transportEntity->getSettingsBag()->get('apiKey');
        if (!$apiKey) {
            throw new RequiredOptionException('apiKey');
        }
        $this->client = $this->mailChimpClientFactory->create($apiKey);
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/helper/ping.php
     * @return array
     */
    public function ping()
    {
        return $this->client->ping();
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/campaigns/list.php
     * @param string|null $status Constant of \OroCRM\Bundle\MailChimpBundle\Entity\Campaign::STATUS_XXX
     * @param bool|null $usesSegment
     * @return \Iterator
     */
    public function getCampaigns($status = null, $usesSegment = null)
    {
        $filters = [];
        if (null !== $status) {
            $filters['status'] = $status;
        }
        if (null !== $usesSegment) {
            $filters['uses_segment'] = (bool)$usesSegment;
        }

        // Synchronize only campaigns that are connected to subscriber lists that are used within OroCRM.
        $staticSegments = $this->managerRegistry
            ->getRepository('OroCRMMailChimpBundle:StaticSegment')
            ->findAll();
        $listsToSynchronize = [];
        foreach ($staticSegments as $staticSegment) {
            $listsToSynchronize[] = $staticSegment->getSubscribersList()->getOriginId();
        }
        $listsToSynchronize = array_unique($listsToSynchronize);

        if ($listsToSynchronize) {
            $filters['list_id'] = implode(',', $listsToSynchronize);
            $filters['exact'] = false;

            return new CampaignIterator($this->client, $filters);
        } else {
            return new \ArrayIterator();
        }
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/lists/list.php
     * @return \Iterator
     */
    public function getLists()
    {
        return new ListIterator($this->client);
    }

    /**
     * Get all members from MailChimp that requires update.
     *
     * @link http://apidocs.mailchimp.com/export/1.0/list.func.php
     *
     * @param \DateTime|null $since
     * @return \Iterator
     */
    public function getMembersToSync(\DateTime $since = null)
    {
        $subscribersLists = $this->managerRegistry->getRepository('OroCRMMailChimpBundle:SubscribersList')
            ->getAllSubscribersListIterator();

        $parameters = ['status' => [Member::STATUS_SUBSCRIBED, Member::STATUS_UNSUBSCRIBED, Member::STATUS_CLEANED]];

        if ($since) {
            $parameters['since'] = $this->getSinceForApi($since);
        }

        return new MemberIterator($subscribersLists, $this->client, $parameters);
    }

    /**
     * Get list of MailChimp Templates.
     *
     * @link http://apidocs.mailchimp.com/api/2.0/templates/list.php
     * @return \Iterator
     */
    public function getTemplates()
    {
        $parameters = [
            'types' => [
                Template::TYPE_USER => true,
                Template::TYPE_GALLERY => true,
                Template::TYPE_BASE => true
            ],
            'filters' => [
                'include_drag_and_drop' => true
            ]
        ];
        return new TemplateIterator($this->client, $parameters);
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/lists/static-segments.php
     */
    public function getSegmentsToSync()
    {
        $subscribersLists = $this->managerRegistry
            ->getRepository('OroCRMMailChimpBundle:SubscribersList')
            ->getUsedSubscribersListIterator();

        $iterator = new StaticSegmentListIterator($subscribersLists, $this->client);

        return $iterator;
    }

    /**
     * @param \DateTime $since
     * @return MemberActivityIterator
     */
    public function getMemberActivitiesToSync(\DateTime $since = null)
    {
        $sentCampaigns = $this->managerRegistry
            ->getRepository('OroCRMMailChimpBundle:Campaign')
            ->getSentCampaigns();

        $parameters = ['include_empty' => false];
        if ($since) {
            $parameters['since'] = $this->getSinceForApi($since);
        }

        return new MemberActivityIterator($sentCampaigns, $this->client, $parameters);
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/lists/batch-subscribe.php
     *
     * @param array $args
     *
     * @return array
     */
    public function batchSubscribe(array $args)
    {
        return $this->client->batchSubscribe($args);
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/lists/static-segment-add.php
     *
     * @param array $args
     *
     * @return array
     */
    public function addStaticListSegment(array $args)
    {
        return $this->client->addStaticListSegment($args);
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/lists/static-segment-members-add.php
     *
     * @param array $args
     *
     * @return array
     */
    public function addStaticSegmentMembers(array $args)
    {
        return $this->client->addStaticSegmentMembers($args);
    }

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/lists/static-segment-members-del.php
     *
     * @param array $args
     *
     * @return array
     */
    public function deleteStaticSegmentMembers(array $args)
    {
        return $this->client->deleteStaticSegmentMembers($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.mailchimp.integration_transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrm_mailchimp_integration_transport_setting_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\\Bundle\\MailChimpBundle\\Entity\\MailChimpTransport';
    }

    /**
     * @param \DateTime $since
     * @return string
     */
    protected function getSinceForApi(\DateTime $since)
    {
        $since = clone $since;
        $since->sub(new \DateInterval('PT1S'));
        $since->setTimezone(new \DateTimeZone('UTC'));

        return $since->format(self::DATETIME_FORMAT);
    }
}
