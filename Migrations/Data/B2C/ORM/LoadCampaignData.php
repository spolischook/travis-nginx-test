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
                'organization uid'
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
            $campaign->setStartDate($this->generateCreatedDate());
            $campaign->setEndDate($this->generateUpdatedDate($campaign->getStartDate()));

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
