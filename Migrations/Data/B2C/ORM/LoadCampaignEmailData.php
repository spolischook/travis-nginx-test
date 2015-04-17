<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;

class LoadCampaignEmailData extends AbstractFixture implements DependentFixtureInterface
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
                'marketing list uid',
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
            __NAMESPACE__ . '\\LoadMarketingListData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'campaigns_emails' => $this->loadData('mailchimp/campaigns_emails.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['campaigns_emails'] as $campaignData) {
            $emailCampaign = new EmailCampaign();
            $emailCampaign->setOwner($this->getUserReference($campaignData['user uid']));
            $emailCampaign->setOrganization($this->getOrganizationReference($campaignData['organization uid']));
            $emailCampaign->setMarketingList($this->getMarketingListReference($campaignData['marketing list uid']));
            $this->setObjectValues($emailCampaign, $campaignData);

            $this->setCampaignEmailReference($campaignData['uid'], $emailCampaign);
            $manager->persist($emailCampaign);
        }
        $manager->flush();

    }
}
