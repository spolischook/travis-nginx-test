<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy as BaseStrategy;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;

class SecurityIdentityRetrievalStrategy extends BaseStrategy
{
    /**
     * @var array Local storage of sids. This local cache increase performance in case if there are a lot of
     *            ACL checks during request.
     *            key => user name
     *            value => array of Sids
     */
    protected $sids = [];

    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $cacheKey = $token->getUsername();

        if (!array_key_exists($cacheKey, $this->sids)) {
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

            $this->sids[$cacheKey] = $sids;
        }

        return $this->sids[$cacheKey];
    }
}
