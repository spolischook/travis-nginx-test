<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\CampaignBundle\Entity\Campaign;

class LoadCampaignData extends AbstractFixture implements DependentFixtureInterface
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadDefaultUserData',
            __NAMESPACE__ . '\\LoadOrganizationData',
        ];
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach($data['campaigns'] as $campaignData)
        {
            $user = $this->getUserReference($campaignData['user uid']);
            $this->setSecurityContext($user);
            $campaign = new Campaign();
            $campaign->setOwner($user);
            $campaign->setOrganization($this->getOrganizationreference($campaignData['organization uid']));

            $this->setObjectValues($campaign, $campaignData);
            $manager->persist($campaign);
            $manager->flush();
        }
    }
}
