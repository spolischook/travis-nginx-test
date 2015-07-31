<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityRetrievalStrategy as BaseStrategy;
use Oro\Bundle\UserBundle\Entity\User;

class SecurityIdentityRetrievalStrategy extends BaseStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = parent::getSecurityIdentities($token);

        // add organization
        if (!$token instanceof AnonymousToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                foreach ($user->getOrganizations() as $organization) {
                    try {
                        $sids[] = OrganizationSecurityIdentity::fromOrganization($organization);
                    } catch (\InvalidArgumentException $invalid) {
                        // ignore, user has no organization security identity
                    }
                }
            }
        }

        return $sids;
    }
}
