<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\MailChimpBundle\Entity\MailChimpTransportSettings;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpCampaignEmailData extends AbstractFixture implements OrderedFixtureInterface
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
     * @return array
     */
    public function getData()
    {
        return [
            'campaigns_emails' => $this->loadData('mailchimp/campaigns_emails.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['campaigns_emails'] as $campaignData) {
            $emailCampaign = new EmailCampaign();
            $transportSettings = new MailChimpTransportSettings();
            $emailCampaign->setTransportSettings($transportSettings);
            $emailCampaign->setOwner($this->getUserReference($campaignData['user uid']));
            $emailCampaign->setOrganization($this->getOrganizationReference($campaignData['organization uid']));
            $emailCampaign->setMarketingList($this->getMarketingListReference($campaignData['marketing list uid']));
            $this->setObjectValues($emailCampaign, $campaignData);

            $this->setCampaignEmailReference($campaignData['uid'], $emailCampaign);
            $manager->persist($emailCampaign);
        }
        $manager->flush();

    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 34;
    }
}
