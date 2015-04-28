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
            static::B2C_NAMESPACE . '\\LoadGroupData',
            static::B2C_NAMESPACE . '\\LoadAccountData',
            static::B2C_NAMESPACE . '\\LoadContactData',
            static::B2C_NAMESPACE . '\\LoadPinBarData',
            static::B2C_NAMESPACE . '\\LoadGroupData',
            static::B2C_NAMESPACE . '\\NavigationHistory\LoadNavigationHistoryItemData',
            static::B2C_NAMESPACE . '\\Tag\LoadTagData',
            static::B2C_NAMESPACE . '\\Tag\LoadAccountTagData',
            static::B2C_NAMESPACE . '\\Tag\LoadContactTagData',
            static::B2C_NAMESPACE . '\\LoadMarketingSegmentData',
            static::B2C_NAMESPACE . '\\LoadMarketingListData',
            static::B2C_NAMESPACE . '\\LoadCampaignData',
            static::B2C_NAMESPACE . '\\LoadCampaignEmailData',
            static::B2C_NAMESPACE . '\\LoadEmailTemplateData',
            static::B2C_NAMESPACE . '\\LoadEmailNotificationData',
            static::B2C_NAMESPACE . '\\LoadUsersCalendarData',
            static::B2C_NAMESPACE . '\\LoadUsersTasksData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpIntegrationData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpCampaignData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpSubscriberListData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpStaticSegmentData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpMemberData',
            static::B2C_NAMESPACE . '\\Activity\LoadCallActivityData',
            static::B2C_NAMESPACE . '\\Activity\LoadEmailActivityData',
            static::B2C_NAMESPACE . '\\Activity\LoadNoteActivityData',
            static::B2C_NAMESPACE . '\\Dashboard\LoadDashboardData',
            static::B2C_NAMESPACE . '\\Dashboard\LoadDashboardWidgetData',
            static::B2C_NAMESPACE . '\\LoadChannelData',
            static::B2C_NAMESPACE . '\\LoadReportData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerCartData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerCartItemData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerGroupData',
            static::B2C_NAMESPACE . '\\Magento\LoadCustomerOrderData',
            static::B2C_NAMESPACE . '\\Magento\LoadMagentoIntegrationData',
            static::B2C_NAMESPACE . '\\Magento\LoadRFMMetricData',
            static::B2C_NAMESPACE . '\\Magento\LoadStoreData',
            static::B2C_NAMESPACE . '\\Magento\LoadWebsiteData',
            static::B2C_NAMESPACE . '\\Tracking\LoadTrackingEventDictionaryData',
            static::B2C_NAMESPACE . '\\Tracking\LoadTrackingWebsiteData',
            static::B2C_NAMESPACE . '\\Tracking\LoadTrackingWebsiteEventData',
            static::B2C_NAMESPACE . '\\Tracking\LoadTrackingWebsiteVisitData',
            static::B2C_NAMESPACE . '\\Tracking\LoadTrackingWebsiteVisitEventData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
    }
}

