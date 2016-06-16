<?php

namespace OroPro\Bundle\SecurityBundle\Tokens;

use Oro\Bundle\SSOBundle\Security\OAuthToken;
use OroPro\Bundle\SecurityBundle\Model\OrganizationTokenTrait;

class ProOAuthToken extends OAuthToken
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
