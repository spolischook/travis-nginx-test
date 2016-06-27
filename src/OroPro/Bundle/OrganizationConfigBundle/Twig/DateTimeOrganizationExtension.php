<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * DateTimeOrganizationExtension allows get formatted date and calendar date range by organization localization settings
 * @package OroPro\Bundle\OrganizationConfigBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in reminder emails CRM-5745 until improvement CRM-5758 is implemented
 */
class DateTimeOrganizationExtension extends DateTimeExtension
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
    public function getFilters()
    {
        $filters = parent::getFilters();
        $filters[] = new \Twig_SimpleFilter(
            'oro_format_datetime_organization',
            [$this, 'formatDateTimeOrganization'],
            ['is_safe' => ['html']]
        );
        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'calendar_date_range_organization' => new \Twig_Function_Method(
                $this,
                'formatCalendarDateRangeOrganization'
            )
        );
    }

    /**
     * Formats date time according to organization locale settings.
     * If organization not passed used localization settings from params
     *
     * Options format:
     * array(
     *     'dateType' => <dateType>,
     *     'timeType' => <timeType>,
     *     'locale' => <locale>,
     *     'timezone' => <timezone>,
     *     'organization' => <organization>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array $options
     * @return string
     */
    public function formatDateTimeOrganization($date, array $options = [])
    {
        $dateType = $this->getOption($options, 'dateType');
        $timeType = $this->getOption($options, 'timeType');
        $organizationId = $this->getOption($options, 'organization');

        /** Get locale and datetime settings from organization configuration if exist */
        if ($organizationId) {
            $data = $this->getOrganizationLocalizationData($organizationId);
            $locale = $data['locale'];
            $timeZone = $data['timeZone'];
        } else {
            $locale = $this->getOption($options, 'locale');
            $timeZone = $this->getOption($options, 'timeZone');
        }

        $result = $this->formatter->format($date, $dateType, $timeType, $locale, $timeZone);

        return $result;
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
     * @param int|null          $organization
     *
     * @return string
     */
    public function formatCalendarDateRangeOrganization(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        $organization = null
    ) {
        if (is_null($startDate)) {
            // exit because nothing to format.
            // We have to accept null as $startDate because the validator of email templates calls functions
            // with empty arguments
            return '';
        }

        if ($organization) {
            $date = $this->getOrganizationLocalizationData($organization);
            $locale = $date['locale'];
            $timeZone = $date['timeZone'];
        }

        // check if $endDate is not specified or $startDate equals to $endDate
        if (is_null($endDate) || $startDate == $endDate) {
            return $skipTime
                ? $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone)
                : $this->formatter->format($startDate, $dateType, $timeType, $locale, $timeZone);
        }

        // check if $startDate and $endDate are the same day
        if ($startDate->format('Ymd') == $endDate->format('Ymd')) {
            if ($skipTime) {
                return $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone);
            }

            return sprintf(
                '%s %s - %s',
                $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone),
                $this->formatter->formatTime($startDate, $timeType, $locale, $timeZone),
                $this->formatter->formatTime($endDate, $timeType, $locale, $timeZone)
            );
        }

        // $startDate and $endDate are different days
        if ($skipTime) {
            return sprintf(
                '%s - %s',
                $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone),
                $this->formatter->formatDate($endDate, $dateType, $locale, $timeZone)
            );
        }

        return sprintf(
            '%s - %s',
            $this->formatter->format($startDate, $dateType, $timeType, $locale, $timeZone),
            $this->formatter->format($endDate, $dateType, $timeType, $locale, $timeZone)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_datetime_organization';
    }
}
