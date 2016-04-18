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
                'campaign uid',
                'coefficient'
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
            $start    = clone $campaign->getStartDate();
            $end      = clone $campaign->getEndDate();

            $website = $this->getTrackingWebsiteReference($summaryData['website uid']);
            for (; $start < $end; $start->modify('+1 day')) {
                $summary = new TrackingEventSummary();
                $summary->setVisitCount($this->generateVisitCount($summaryData['coefficient']));
                $summary->setLoggedAt(clone $start);
                $summary->setCode($campaign->getCode());
                $summary->setWebsite($website);
                $this->setObjectValues($summary, $summaryData);
                $manager->persist($summary);
            }
        }
        $manager->flush();
    }

    public function generateVisitCount($coefficient = 1)
    {
        $coefficient = (int)$coefficient;

        return rand(1 * $coefficient, 10 * $coefficient);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 45;
    }
}
