<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tracking;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTrackingWebsiteVisitEventData extends AbstractFixture implements OrderedFixtureInterface
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
                'customer uid',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'visits' => $this->loadData('tracking/tracking_visits_events.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['visits'] as $visitEventData) {
            $visitEvent = new TrackingVisitEvent();
            $visitEvent->setWebsite($this->getTrackingWebsiteReference($visitEventData['website uid']));
            $visitEvent->setWebEvent($this->getTrackingEventReference($visitEventData['tracking event uid']));
            $visitEvent->setEvent(
                $this->getTrackingEventDictionaryReference($visitEventData['tracking event dictionary uid'])
            );
            $visit = $this->getTrackingVisitReference($visitEventData['tracking visit uid']);
            $visitEvent->setVisit($visit);
            $visitEvent->addAssociationTarget($visit->getIdentifierTarget());
            $manager->persist($visitEvent);
        }
        $manager->flush();
    }


    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 44;
    }
}
