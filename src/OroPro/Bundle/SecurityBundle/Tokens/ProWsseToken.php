<?php

namespace OroPro\Bundle\SecurityBundle\Tokens;

use Oro\Bundle\UserBundle\Security\WsseToken;
use OroPro\Bundle\SecurityBundle\Model\OrganizationTokenTrait;

class ProWsseToken extends WsseToken
{
    use OrganizationTokenTrait;

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $organization = $this->getOrganizationContext();
        $roles = parent::getRoles();
        if (!$organization) {
            return $roles;
        }

        $roles = $this->filterRolesInOrganizationContext($organization, $roles);

        return $roles;
    }
}
