<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tracking;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTrackingWebsiteEventData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'website uid',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'events' => $this->loadData('tracking/tracking_websites_events.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getClassMetadata('Oro\Bundle\TrackingBundle\Entity\TrackingEvent')->setLifecycleCallbacks([]);

        $data = $this->getData();

        foreach ($data['events'] as $eventData) {
            $event = new TrackingEvent();
            $this->setObjectValues($event, $eventData);
            $event->setWebsite($this->getTrackingWebsiteReference($eventData['website uid']));
            $event->setCreatedAt($this->generateCreatedDate());
            $event->setLoggedAt($event->getCreatedAt());

            $this->setTrackingEventReference($eventData['uid'], $event);
            $manager->persist($event);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 42;
    }
}
