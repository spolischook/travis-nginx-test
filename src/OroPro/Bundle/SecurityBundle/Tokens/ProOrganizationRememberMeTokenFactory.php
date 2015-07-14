<?php

namespace OroPro\Bundle\SecurityBundle\Tokens;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface;

class ProOrganizationRememberMeTokenFactory implements OrganizationRememberMeTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(UserInterface $user, $providerKey, $key, Organization $organizationContext)
    {
        $authenticatedToken = new ProOrganizationRememberMeToken(
            $user,
            $providerKey,
            $key,
            $organizationContext
        );

        return $authenticatedToken;
    }
}
