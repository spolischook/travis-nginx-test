<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Zendesk;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\UserConnector;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadZendeskIntegrationData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'integrations' => $this->loadData('zendesk/integrations.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['integrations'] as $integrationData) {
            $transport = new ZendeskRestTransport();
            $this->setObjectValues(
                $transport,
                $integrationData,
                array('uid','organization uid', 'name','reference')
            );
            $manager->persist($transport);

            $integration = new Integration();
            $integration->setDefaultUserOwner($this->getMainUser());
            $integration->setType(ChannelType::TYPE);
            $integration->setName($integrationData['name']);
            $integration->setTransport($transport);
            $integration->setOrganization($this->getOrganizationReference($integrationData['organization uid']));
            $integration->setConnectors(
                [
                    TicketConnector::TYPE,
                    UserConnector::TYPE,
                    TicketCommentConnector::TYPE
                ]
            );
            $integration->setEnabled(true);
            $this->setZendeskIntegrationReference($integrationData['uid'], $integration);
            $manager->persist($integration);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 34;
    }
}
