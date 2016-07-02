<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class OrganizationConfigHelper
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Get locale and datetime settings from organization configuration if exist
     *
     * @param int    $organizationId
     * @param string $configName
     *
     * @return array
     * @throws \Exception
     */
    public function getOrganizationScopeConfig($organizationId, $configName)
    {
        $prevScopeId = $this->configManager->getScopeId();
        try {
            $this->configManager->setScopeId($organizationId);

            $config = $this->configManager->get($configName);

            $this->configManager->setScopeId($prevScopeId);
        } catch (\Exception $e) {
            $this->configManager->setScopeId($prevScopeId);

            throw $e;
        }

        return $config;
    }
}
