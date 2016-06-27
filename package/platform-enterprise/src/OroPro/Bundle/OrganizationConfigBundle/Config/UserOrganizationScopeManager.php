<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;

class UserOrganizationScopeManager extends UserScopeManager
{
    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'organization_user';
    }

    /**
     * {@inheritdoc}
     */
    protected function ensureScopeIdInitialized()
    {
        if (null === $this->scopeId) {
            $scopeId = 0;

            $token = $this->securityContext->getToken();
            if ($token instanceof OrganizationContextTokenInterface) {
                $user = $token->getUser();
                if ($user instanceof User && $user->getId()) {
                    $organization = $token->getOrganizationContext();
                    if ($organization instanceof Organization && $organization->getId()) {
                        $scopeId = $this->getUserOrganizationId($user, $organization);
                    }
                }
            }

            $this->scopeId = $scopeId;
        }
    }

    /**
     * @param User         $user
     * @param Organization $organization
     *
     * @return int
     */
    protected function getUserOrganizationId(User $user, Organization $organization)
    {
        return $this->doctrine->getManagerForClass('OroProOrganizationBundle:UserOrganization')
            ->getRepository('OroProOrganizationBundle:UserOrganization')
            ->getUserOrganization($user, $organization)
            ->getId();
    }
}
