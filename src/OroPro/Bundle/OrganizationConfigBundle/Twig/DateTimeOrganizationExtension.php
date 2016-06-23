<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

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
            /** @var ConfigManager $configManager */
            $configManager = $this->container->get('oro_config.organization');
            $prevScopeId = $configManager->getScopeId();
            $configManager->setScopeId($organizationId);

            $locale = $configManager->get('oro_locale.locale');
            $timeZone = $configManager->get('oro_locale.timezone');

            $configManager->setScopeId($prevScopeId);
        } else {
            $locale = $this->getOption($options, 'locale');
            $timeZone = $this->getOption($options, 'timeZone');
        }

        return $this->formatter->format($date, $dateType, $timeType, $locale, $timeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_organization_config_datetime_organization';
    }
}
