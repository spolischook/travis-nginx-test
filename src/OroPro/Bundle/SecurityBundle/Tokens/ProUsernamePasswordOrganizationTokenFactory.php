<?php

namespace OroPro\Bundle\SecurityBundle\Tokens;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;

class ProUsernamePasswordOrganizationTokenFactory implements UsernamePasswordOrganizationTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($user, $credentials, $providerKey, Organization $organizationContext, array $roles = [])
    {
        $authenticatedToken = new ProUsernamePasswordOrganizationToken(
            $user,
            $credentials,
            $providerKey,
            $organizationContext,
            $roles
        );

        return $authenticatedToken;
    }
}
