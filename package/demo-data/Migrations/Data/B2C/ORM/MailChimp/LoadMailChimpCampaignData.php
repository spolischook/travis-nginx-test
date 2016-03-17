<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\MailChimpBundle\Entity\Campaign;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpCampaignData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid',
                'integration uid',
                'campaign email uid',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'campaigns' => $this->loadData('mailchimp/campaigns.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['campaigns'] as $campaignData) {
            $campaign = new Campaign();
            $this->setObjectValues($campaign, $campaignData);

            $campaign->setEmailCampaign($this->getEmailCampaignReference($campaignData['campaign email uid']));
            $campaign->setOwner($this->getOrganizationReference($campaignData['organization uid']));
            $campaign->setChannel($this->getMailChimpIntegrationReference($campaignData['integration uid']));
            $manager->persist($campaign);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 35;
    }
}
