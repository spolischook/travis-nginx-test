<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\UserConnector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class LoadZendeskIntegrationData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'transports' => $this->loadData('zendesk/integrations.csv'),
        ];
    }

    protected $channelData = array(
        array(
            'name'         => 'Demo Zendesk integration',
            'type'         => ChannelType::TYPE,
            'connectors'   => array(
                TicketConnector::TYPE,
                UserConnector::TYPE,
                TicketCommentConnector::TYPE
            ),
            'enabled'      => 0,
            'transport'    => 'orocrm_zendesk:zendesk_demo_transport',
            'reference'    => 'orocrm_zendesk:zendesk_demo_channel',
            'organization' => null
        )
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->channelData as $data) {
            $channel = new Channel();

            $data['transport']    = $this->getReference($data['transport']);
            $data['organization'] = $this->getReference('default_organization');

            $this->setEntityPropertyValues($channel, $data, array('reference'));
            $manager->persist($channel);

            $this->setReference($data['reference'], $channel);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 34;
    }

}
