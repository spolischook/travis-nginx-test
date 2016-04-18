<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\ReportBundle\EventListener\NavigationListener;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class ReportNavigationListener extends NavigationListener
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAvailability(Config $config)
    {
        if (parent::checkAvailability($config)) {
            $applicable = $this->getOrganizationConfig($config)->get('applicable');
            return
                $applicable
                && (
                    $applicable['all'] == true
                    || in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])
                );
        }

        return false;
    }

    /**
     * @param Config $config
     *
     * @return ConfigInterface
     */
    protected function getOrganizationConfig(Config $config)
    {
        $className                  = $config->getId()->getClassname();
        $configManager              = $this->entityConfigProvider->getConfigManager();
        $organizationConfigProvider = $configManager->getProvider('organization');

        return $organizationConfigProvider->getConfig($className);
    }
}
