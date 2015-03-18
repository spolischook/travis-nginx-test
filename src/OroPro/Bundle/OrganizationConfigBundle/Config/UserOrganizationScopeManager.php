<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Config;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;

class UserOrganizationScopeManager extends UserScopeManager
{
    const SCOPED_ENTITY_NAME = 'organization_user';

    /**
     * @var User
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return self::SCOPED_ENTITY_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecurity(SecurityContextInterface $security)
    {
        $this->security = $security;

        // if we have a user - try to merge his scoped settings into global settings array
        if ($token = $this->security->getToken()) {
            /** @var TokenInterface $token */
            if (is_object($user = $token->getUser()) && is_object($organization = $token->getOrganizationContext())) {
                foreach ($user->getGroups() as $group) {
                    $this->loadStoredSettings('group', $group->getId());
                }

                $this->loadStoredSettings(
                    self::SCOPED_ENTITY_NAME,
                    $this->getUserOrganizationId($user, $organization)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeId($scopeId = null)
    {
        if (is_null($scopeId)) {
            $scopeId = 0;
            if ($token = $this->security->getToken()) {
                if (is_object($user = $token->getUser()) &&
                    is_object($organization = $token->getOrganizationContext()) &&
                    !is_null($organization->getId())) {
                    $scopeId = $this->getUserOrganizationId($user, $organization);
                }
            }
        }

        $this->scopeId = $scopeId;
        $this->loadStoredSettings($this->getScopedEntityName(), $this->scopeId);

        return $this;
    }

    /**
     * @param User $user
     * @param Organization $organization
     * @return int
     */
    public function getUserOrganizationId(User $user, Organization $organization)
    {
        return $this->om->getRepository('OroProOrganizationBundle:UserOrganization')
            ->getUserOrganization($user, $organization)
            ->getId();
    }
}
