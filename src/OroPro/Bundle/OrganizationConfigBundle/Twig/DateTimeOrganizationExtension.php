<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Oro\Bundle\LocaleBundle\Twig\DateTimeOrganizationExtension as BaseDateTimeOrganizationExtension;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use OroPro\Bundle\OrganizationConfigBundle\Helper\OrganizationConfigHelper;

/**
 * DateTimeUserExtension allows get formatted date and calendar date range by user organization localization settings
 * @package OroPro\Bundle\OrganizationConfigBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in reminder emails CRM-5745 until improvement CRM-5758 is implemented
 */
class DateTimeOrganizationExtension extends BaseDateTimeOrganizationExtension
{
    /**
     * @var OrganizationConfigHelper
     */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function getLocaleSettings($organization, array $options)
    {
        if ($organization instanceof OrganizationInterface) {
            $organizationId = $organization->getId();

            $locale = $this->helper->getOrganizationScopeConfig($organizationId, 'oro_locale.locale');
            $timeZone = $this->helper->getOrganizationScopeConfig($organizationId, 'oro_locale.timezone');

            return [$locale, $timeZone];
        }

        return parent::getLocaleSettings($organization, $options);
    }

    /**
     * @param OrganizationConfigHelper $helper
     */
    public function setHelper(OrganizationConfigHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_datetime_organization';
    }
}
