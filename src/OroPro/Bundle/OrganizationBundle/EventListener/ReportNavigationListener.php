<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\ReportBundle\EventListener\NavigationListener;

class ReportNavigationListener extends NavigationListener
{
    /**
     * {@inheritdoc}
     */
    public function checkAvailability(Config $config)
    {
        if (!parent::checkAvailability($config)) {
            return false;
        }
        $organizationConfig = $this->getOrganizationConfig($config);
        if ($organizationConfig->has('applicable')) {
            $applicable = $organizationConfig->get('applicable');

            return (
                $applicable['all'] == true
                || in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])
            );
        }

        return true;
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
