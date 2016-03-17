<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tracking;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\CampaignBundle\Entity\TrackingEventSummary;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTrackingCampaignSummaryData extends AbstractFixture implements OrderedFixtureInterface
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
                'campaign uid'
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'campaign_summary' => $this->loadData('tracking/tracking_campaign_summary.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['campaign_summary'] as $summaryData) {
            $campaign = $this->getCampaignReference($summaryData['campaign uid']);
            $start    = $campaign->getStartDate();
            $end      = $campaign->getEndDate();

            $website = $this->getTrackingWebsiteReference($summaryData['website uid']);
            for (; $start < $end; $start->modify('+1 day')) {
                $summary = new TrackingEventSummary();
                $summary->setVisitCount(rand(100, 1000));
                $summary->setLoggedAt(clone $start);
                $summary->setCode($campaign->getCode());
                $summary->setWebsite($website);
                $this->setObjectValues($summary, $summaryData);
                $manager->persist($summary);
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 45;
    }
}
