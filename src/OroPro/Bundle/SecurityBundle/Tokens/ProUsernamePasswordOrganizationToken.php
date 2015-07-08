<?php

namespace OroPro\Bundle\SecurityBundle\Tokens;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use OroPro\Bundle\SecurityBundle\Model\OrganizationTokenTrait;

class ProUsernamePasswordOrganizationToken extends UsernamePasswordOrganizationToken
{
    use OrganizationTokenTrait;

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $organization = $this->getOrganizationContext();
        $roles = parent::getRoles();

        $roles = $this->filterRolesInOrganizationContext($organization, $roles);

        return $roles;
    }
}
