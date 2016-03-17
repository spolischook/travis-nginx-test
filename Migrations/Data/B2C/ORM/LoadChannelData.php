<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\ChannelBundle\Builder\BuilderFactory;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\SalesBundle\Migrations\Data\ORM\DefaultChannelData;

class LoadChannelData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'channels' => $this->loadData('channels.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var BuilderFactory $factory */
        $factory = $this->container->get('orocrm_channel.builder.factory');

        $data = $this->getData();
        foreach ($data['channels'] as $channelData) {
            $channel = $factory->createBuilder()
                ->setStatus(Channel::STATUS_ACTIVE)
                ->setEntities()
                ->setChannelType(DefaultChannelData::B2B_CHANNEL_TYPE)
                ->setName($channelData['name'])
                ->setOwner($this->getOrganizationReference($channelData['organization uid']))
                ->getChannel();
            $this->setChannelReference($channelData['uid'], $channel);
            $manager->persist($channel);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }
}
