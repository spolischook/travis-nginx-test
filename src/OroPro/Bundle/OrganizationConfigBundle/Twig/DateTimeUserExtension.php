<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Oro\Bundle\LocaleBundle\Twig\DateTimeUserExtension as BaseDateTimeUserExtension;

use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationConfigBundle\Helper\OrganizationConfigHelper;

/**
 * DateTimeUserExtension allows get formatted date and calendar date range by user organization localization settings
 * @package OroPro\Bundle\OrganizationConfigBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in reminder emails CRM-5745 until improvement CRM-5758 is implemented
 */
class DateTimeUserExtension extends BaseDateTimeUserExtension
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
     * Formats date time according to user organization locale settings.
     * If user not passed used localization settings from params
     *
     * Options format:
     * array(
     *     'dateType' => <dateType>,
     *     'timeType' => <timeType>,
     *     'locale' => <locale>,
     *     'timezone' => <timezone>,
     *     'user' => <user>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array $options
     * @return string
     */
    public function formatDateTimeUser($date, array $options = [])
    {
        $dateType = $this->getOption($options, 'dateType');
        $timeType = $this->getOption($options, 'timeType');
        $user = $this->getOption($options, 'user');

        /** Get locale and datetime settings from organization configuration if exist */
        if ($user instanceof User) {
            $organizationId = $user->getOrganization()->getId();

            $locale = $this->helper->getOrganizationScopeConfig($organizationId, 'oro_locale.locale');
            $timeZone = $this->helper->getOrganizationScopeConfig($organizationId, 'oro_locale.timezone');
        } else {
            $locale = $this->getOption($options, 'locale');
            $timeZone = $this->getOption($options, 'timeZone');
        }

        $result = $this->formatter->format($date, $dateType, $timeType, $locale, $timeZone);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_datetime_user';
    }
}
