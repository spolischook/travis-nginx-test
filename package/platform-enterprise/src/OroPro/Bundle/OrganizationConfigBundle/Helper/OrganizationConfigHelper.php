<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class OrganizationConfigHelper
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get locale and datetime settings from organization configuration if exist
     *
     * @param int $organizationId
     * @return array
     */
    public function getOrganizationLocalizationData($organizationId)
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
}
