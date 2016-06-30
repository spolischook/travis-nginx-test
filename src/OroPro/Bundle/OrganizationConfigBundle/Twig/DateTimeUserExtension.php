<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * DateTimeUserExtension allows get formatted date and calendar date range by user organization localization settings
 * @package OroPro\Bundle\OrganizationConfigBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in reminder emails CRM-5745 until improvement CRM-5758 is implemented
 */
class DateTimeUserExtension extends DateTimeExtension
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
            'oro_format_datetime_user',
            [$this, 'formatDateTimeUser'],
            ['is_safe' => ['html']]
        );
        return $filters;
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
        if ($user && $user->getOrganization()->getId()) {
            $organizationId = $user->getOrganization()->getId();
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_datetime_user';
    }
}
