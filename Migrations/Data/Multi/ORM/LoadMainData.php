<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\Multi\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMainData extends AbstractFixture implements DependentFixtureInterface
{
    const B2C_NAMESPACE = 'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM';

    const B2B_NAMESPACE = 'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            static::B2C_NAMESPACE . '\\LoadConfigData',
            static::B2C_NAMESPACE . '\\LoadGroupData',
            static::B2C_NAMESPACE . '\\LoadPinBarData',
            static::B2C_NAMESPACE . '\\LoadEmailTemplateData',
            static::B2C_NAMESPACE . '\\LoadEmailNotificationData',
            /** Tag data */
            static::B2C_NAMESPACE . '\\Tag\LoadTagData',
            static::B2C_NAMESPACE . '\\Tag\LoadAccountTagData',
            static::B2C_NAMESPACE . '\\Tag\LoadContactTagData',
            /** Marketing data */
            static::B2C_NAMESPACE . '\\LoadMarketingSegmentData',
            static::B2C_NAMESPACE . '\\LoadMarketingListData',
            /** Users data */
            static::B2C_NAMESPACE . '\\LoadUsersCalendarData',
            static::B2C_NAMESPACE . '\\LoadUsersTasksData',
            /** MailChimp data */
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpCampaignEmailData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpIntegrationData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpCampaignData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpSubscriberListData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpStaticSegmentData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpMemberData',
            /** Magento data */
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerCartData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerCartItemData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerGroupData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerOrderData',
            static::B2C_NAMESPACE . '\\Magento\LoadMagentoIntegrationData',
            static::B2C_NAMESPACE . '\\Magento\LoadRFMMetricData',
            static::B2C_NAMESPACE . '\\Magento\LoadStoreData',
            static::B2C_NAMESPACE . '\\Magento\LoadWebsiteData',
            /** Zendesk data */
            static::B2C_NAMESPACE . '\\Zendesk\LoadZendeskIntegrationData',
            static::B2C_NAMESPACE . '\\Zendesk\LoadTicketEntityData',
            /** B2B data */
            static::B2B_NAMESPACE . '\\B2B\LoadLeadSourceData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
    }
}
