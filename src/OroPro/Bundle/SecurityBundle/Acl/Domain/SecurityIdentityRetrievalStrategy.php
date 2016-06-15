<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy as BaseStrategy;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;

class SecurityIdentityRetrievalStrategy extends BaseStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = parent::getSecurityIdentities($token);

        if (!$token instanceof AnonymousToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                foreach ($user->getBusinessUnits() as $businessUnit) {
                    $sids[] = BusinessUnitSecurityIdentity::fromBusinessUnit($businessUnit);
                }
                foreach ($user->getOrganizations() as $organization) {
                    $sids[] = OrganizationSecurityIdentity::fromOrganization($organization);
                }
            }
        }

        return $sids;
    }
}
