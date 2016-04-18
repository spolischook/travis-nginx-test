<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tracking;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTrackingEventDictionaryData extends AbstractFixture implements OrderedFixtureInterface
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
            'events_dictionaries' => $this->loadData('tracking/tracking_events_dictionaries.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['events_dictionaries'] as $dictionaryData) {
            $dictionary = new TrackingEventDictionary();

            $website = $this->getTrackingWebsiteReference($dictionaryData['website uid']);
            $this->setObjectValues($dictionary, $dictionaryData);
            $dictionary->setWebsite($website);

            $this->setTrackingEventDictionaryReference($dictionaryData['uid'], $dictionary);
            $manager->persist($dictionary);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 41;
    }
}
