<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityBundle\EventListener\NavigationListener as BaseNavigationListener;

class NavigationListener extends BaseNavigationListener
{
    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(Config $extendConfig)
    {
        if (parent::checkAvailability($extendConfig)) {
            $className                  = $extendConfig->getId()->getClassname();
            $organizationConfigProvider = $this->configManager->getProvider('organization');
            $organizationConfig         = $organizationConfigProvider->getConfig($className);
            $applicable = $organizationConfig->get('applicable', false, false);
            return
                $applicable
                && (
                    $applicable['all']
                    || in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])
                );
        }
    }
}
