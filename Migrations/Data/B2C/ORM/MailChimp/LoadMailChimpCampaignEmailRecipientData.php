<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListItem;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpCampaignEmailRecipientData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'campaigns_emails_recipients' => $this->loadData('mailchimp/campaigns_emails_recipients.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['campaigns_emails_recipients'] as $recipientsData) {
            $campaign = $this->getEmailCampaignReference($recipientsData['email campaign uid']);

            // Fetch entity by uid and campaign class
            $class     = $campaign->getMarketingList()->getEntity();
            $reference = substr($class, strrpos($class, '\\') + 1);
            $reference = str_replace('B2b', '', $reference);
            $entity    = $this->getReferenceByName(sprintf('%s:%s', $reference, $recipientsData['entity uid']));

            $marketingListItem = new MarketingListItem();
            $marketingListItem->setMarketingList($campaign->getMarketingList());
            $marketingListItem->setContactedTimes($recipientsData['contacted times']);
            $marketingListItem->setEntityId($entity->getId());
            $marketingListItem->setCreatedAt($this->generateCreatedDate());
            $marketingListItem->setLastContactedAt($this->generateUpdatedDate($marketingListItem->getCreatedAt()));
            $manager->persist($marketingListItem);

            $emailCampaignStatistics = new EmailCampaignStatistics();
            $emailCampaignStatistics->setOwner($entity->getOwner());
            $emailCampaignStatistics->setOrganization($campaign->getOrganization());
            $emailCampaignStatistics->setEmailCampaign($campaign);
            $emailCampaignStatistics->setMarketingListItem($marketingListItem);
            $emailCampaignStatistics->setCreatedAt($this->generateCreatedDate());
            $emailCampaignStatistics->setOpenCount($recipientsData['open']);
            $emailCampaignStatistics->setClickCount($recipientsData['click']);
            $emailCampaignStatistics->setBounceCount($recipientsData['bounce']);
            $emailCampaignStatistics->setAbuseCount($recipientsData['abuse']);
            $emailCampaignStatistics->setUnsubscribeCount($recipientsData['unsubscribe']);
            $manager->persist($emailCampaignStatistics);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 60;
    }
}
