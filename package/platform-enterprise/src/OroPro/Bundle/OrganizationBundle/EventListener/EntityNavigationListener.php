<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityBundle\EventListener\NavigationListener;

class EntityNavigationListener extends NavigationListener
{
    /**
     * {@inheritdoc}
     */
    public function checkAvailability(Config $extendConfig)
    {
        // In System access mode we should not check entities availability per organization
        if ($this->securityFacade->getOrganization() && $this->securityFacade->getOrganization()->getIsGlobal()) {
            return parent::checkAvailability($extendConfig);
        }

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

        return false;
    }
}
