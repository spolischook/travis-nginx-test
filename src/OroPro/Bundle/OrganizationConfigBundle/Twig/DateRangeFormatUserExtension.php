<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\CalendarBundle\Twig\DateFormatExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

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
     * @var DateTimeFormatter
     */
    protected $formatter;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'calendar_date_range_user' => new \Twig_Function_Method(
                $this,
                'formatCalendarDateRangeUser'
            )
        );
    }

    /**
     * Returns a string represents a range between $startDate and $endDate, formatted according the given parameters
     * Examples:
     *      $endDate is not specified
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate equals to $endDate
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate and $endDate are the same day
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:00pm – 5:30pm - when $skipTime = false
     *      $startDate and $endDate are different days
     *          Thu Oct 17, 2013 5:00pm – Thu Oct 18, 2013 5:00pm - when $skipTime = false
     *          Thu Oct 17, 2013 – Thu Oct 18, 2013 - when $skipTime = true
     *
     * @param \DateTime|null    $startDate
     * @param \DateTime|null    $endDate
     * @param bool              $skipTime
     * @param string|int|null   $dateType \IntlDateFormatter constant or it's string name
     * @param string|int|null   $timeType \IntlDateFormatter constant or it's string name
     * @param string|null       $locale
     * @param string|null       $timeZone
     * @param User|null          $user
     *
     * @return string
     */
    public function formatCalendarDateRangeUser(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        $user = null
    ) {
        // Get localization settings from user organization scope
        if ($user) {
            $organizationId = $user->getOrganization()->getId();
            $date = $this->getOrganizationLocalizationData($organizationId);
            $locale = $date['locale'];
            $timeZone = $date['timeZone'];
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
     * Get locale and datetime settings from organization configuration if exist
     *
     * @param int $organizationId
     * @return array
     */
    protected function getOrganizationLocalizationData($organizationId)
    {
        $data = ['locale' => null, 'timeZone' => null];
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.organization');
        $prevScopeId = $configManager->getScopeId();
        try {
            $configManager->setScopeId($organizationId);
            $data['locale'] = $configManager->get('oro_locale.locale');
            $data['timeZone'] = $configManager->get('oro_locale.timezone');
        } finally {
            $configManager->setScopeId($prevScopeId);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_daterange_format_user';
    }
}
