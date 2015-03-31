<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;

class LoadTrackingWebsiteEventData extends AbstractFixture implements DependentFixtureInterface
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadTrackingWebsiteData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'events' => $this->loadData('marketing/tracking_websites_events.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['events'] as $eventData) {
            $event = new TrackingEvent();
            $this->setObjectValues($event, $eventData);
            $event->setWebsite($this->getTrackingWebsiteReference($eventData['website uid']));
            $event->setCreatedAt($this->generateCreatedDate());
            $event->setLoggedAt($event->getCreatedAt());

            $manager->getClassMetadata(get_class($event))->setLifecycleCallbacks([]);
            $manager->persist($event);
        }
        $manager->flush();
    }
}
