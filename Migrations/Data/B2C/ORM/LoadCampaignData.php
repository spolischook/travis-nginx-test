<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\CampaignBundle\Entity\Campaign;

class LoadCampaignData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'organization uid',
                'start date',
                'end date'
            ]
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'campaigns' => $this->loadData('campaigns.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['campaigns'] as $campaignData) {
            $user = $this->getUserReference($campaignData['user uid']);
            $this->setSecurityContext($user);
            $campaign = new Campaign();
            $campaign->setOwner($user);
            $campaign->setOrganization($this->getOrganizationreference($campaignData['organization uid']));

            if(!empty($campaignData['start date']) && !empty($campaignData['end date'])){
                $campaign->setStartDate(new \DateTime($campaignData['start date']));
                $campaign->setEndDate(new \DateTime($campaignData['end date']));
            } else {
                $created = $this->generateCreatedDate();
                $campaign->setEndDate($this->generateUpdatedDate($created));
                $campaign->setStartDate($created->modify('-1 week'));
            }
            $this->setObjectValues($campaign, $campaignData);
            $manager->persist($campaign);
            $this->setCampaignReference($campaignData['uid'], $campaign);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 13;
    }
}
