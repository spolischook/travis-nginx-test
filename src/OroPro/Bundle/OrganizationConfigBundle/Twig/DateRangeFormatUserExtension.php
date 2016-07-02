<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Oro\Bundle\CalendarBundle\Twig\DateFormatExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;

use OroPro\Bundle\OrganizationConfigBundle\Helper\OrganizationConfigHelper;

/**
 * DateTimeUserExtension allows get formatted date range by user organization localization settings
 * @package OroPro\Bundle\OrganizationConfigBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in reminder emails CRM-5745 until improvement CRM-5758 is implemented
 */
class DateRangeFormatUserExtension extends DateFormatExtension
{
    /**
     * @var OrganizationConfigHelper
     */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    public function setHelper(OrganizationConfigHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function formatCalendarDateRangeUser(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        UserInterface $user = null
    ) {
        // Get localization settings from user organization scope
        if ($user instanceof User) {
            $organizationId = $user->getOrganization()->getId();

            $locale = $this->helper->getOrganizationScopeConfig($organizationId, 'oro_locale.locale');
            $timeZone = $this->helper->getOrganizationScopeConfig($organizationId, 'oro_locale.timezone');
        }

        return $this->formatCalendarDateRange(
            $startDate,
            $endDate,
            $skipTime,
            $dateType,
            $timeType,
            $locale,
            $timeZone
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_daterange_format_user';
    }
}
