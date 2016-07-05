<?php

namespace OroPro\Bundle\CalendarBundle\Twig;

use Oro\Bundle\CalendarBundle\Twig\DateFormatExtension;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

use OroPro\Bundle\LocaleBundle\Helper\OrganizationConfigHelper;

/**
 * DateTimeUserExtension allows get formatted date range by user organization localization settings
 * @package OroPro\Bundle\OrganizationConfigBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in notification emails until improvement CRM-5758 is implemented
 */
class DateRangeFormatOrganizationExtension extends DateFormatExtension
{
    /**
     * @var OrganizationConfigHelper
     */
    protected $helper;

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
    protected function getOrganizationLocaleSettings(OrganizationInterface $organization)
    {
        $locale = $this->helper->getOrganizationScopeConfig($organization->getId(), 'oro_locale.locale');
        $timeZone = $this->helper->getOrganizationScopeConfig($organization->getId(), 'oro_locale.timezone');

        return [$locale, $timeZone];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_calendar_daterange_format_organization';
    }
}
