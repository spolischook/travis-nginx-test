<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Manager;

use Oro\Bundle\ConfigBundle\Manager\GlobalConfigManager as BaseManager;
use Oro\Bundle\UserBundle\Entity\User;

class GlobalConfigManager extends BaseManager
{
    /**
     * Sets context of user
     *
     * @param User $user
     * @return bool
     */
    protected function setContext(User $user)
    {
        if (in_array(null, [$this->configManager, $this->userScopeManager], true)) {
            throw new \RuntimeException('Unable to save user config, unmet dependencies detected.');
        }

        $this->configManager->addManager($this->userScopeManager->getScopedEntityName(), $this->userScopeManager);
        $this->configManager->setScopeName($this->userScopeManager->getScopedEntityName());
        $preferredOrganizationId = $this->userScopeManager->getPreferredOrganizationId($user, $user->getOrganization());
        if (!$preferredOrganizationId) {
            return false;
        }
        $this->configManager->setScopeId($preferredOrganizationId);

        return true;
    }
}
