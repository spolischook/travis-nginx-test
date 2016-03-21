<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMainData extends AbstractFixture implements DependentFixtureInterface
{
    const B2C_NAMESPACE = 'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            static::B2C_NAMESPACE . '\\LoadConfigData',
            static::B2C_NAMESPACE . '\\LoadOrganizationData',
            static::B2C_NAMESPACE . '\\LoadBusinessUnitData',
            static::B2C_NAMESPACE . '\\LoadDefaultUserData',
            static::B2C_NAMESPACE . '\\LoadGroupData',
            static::B2C_NAMESPACE . '\\LoadEmailTemplateData',
            static::B2C_NAMESPACE . '\\LoadEmailNotificationData',
            static::B2C_NAMESPACE . '\\NavigationHistory\LoadNavigationHistoryItemData',
            /** Tag data */
            static::B2C_NAMESPACE . '\\Tag\LoadTagData',
            static::B2C_NAMESPACE . '\\Tag\LoadAccountTagData',
            static::B2C_NAMESPACE . '\\Tag\LoadContactTagData',
            /** User data */
            static::B2C_NAMESPACE . '\\LoadUsersCalendarData',
            static::B2C_NAMESPACE . '\\LoadUsersTasksData',
            /** Mailchimp data*/
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\MailChimp\LoadMailChimpCampaignEmailData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpIntegrationData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\MailChimp\LoadMailChimpCampaignData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpSubscriberListData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpStaticSegmentData',
            static::B2C_NAMESPACE . '\\MailChimp\LoadMailChimpMemberData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
    }
}
