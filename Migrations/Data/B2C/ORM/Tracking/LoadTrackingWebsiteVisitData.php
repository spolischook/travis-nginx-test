<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tracking;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTrackingWebsiteVisitData extends AbstractFixture implements DependentFixtureInterface
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
            'visits' => $this->loadData('tracking/tracking_websites_visits.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['visits'] as $visitData) {
            $visit = new TrackingVisit();
            $this->setObjectValues($visit, $visitData);
            $visit->setTrackingWebsite($this->getTrackingWebsiteReference($visitData['website uid']));
            $visit->setFirstActionTime($this->generateCreatedDate());
            $visit->setLastActionTime($visit->getFirstActionTime());

            if(!empty($visitData['customer uid'])) {
                $customer = $this->getCustomerReference($visitData['customer uid']);
                $visit->setIdentifierTarget($customer);
            }

            $this->setTrackingVisitReference($visitData['uid'], $visit);
            $manager->persist($visit);
        }
        $manager->flush();
    }
}
