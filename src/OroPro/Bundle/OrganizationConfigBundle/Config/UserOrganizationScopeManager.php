<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Config;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;

class UserOrganizationScopeManager extends UserScopeManager
{
    const SCOPED_ENTITY_NAME = 'pro_organization_user_pref';

    /**
     * @var UserPreferredOrganization
     */
    protected $preferredOrg;

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
            /** @var Organization $token */
            if (is_object($user = $token->getUser()) && is_object($organization = $token->getOrganizationContext())) {
                foreach ($user->getGroups() as $group) {
                    $this->loadStoredSettings('group', $group->getId());
                }

                $this->loadStoredSettings(
                    self::SCOPED_ENTITY_NAME,
                    $this->getPreferredOrganizationId($user, $organization)
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
            if ($token = $this->security->getToken()) {
                if (is_object($user = $token->getUser()) &&
                    is_object($organization = $token->getOrganizationContext())) {
                    $scopeId = $this->getPreferredOrganizationId($user, $organization);
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
    public function getPreferredOrganizationId(User $user, Organization $organization = null)
    {
        $id = 0;
        if ($organization) {
            if (is_null($this->preferredOrg)
                || $this->preferredOrg->getOrganization()->getId() != $organization->getId()
                || $this->user->getId() != $user->getId()) {
                $this->preferredOrg = $this->om->getRepository('OroProOrganizationBundle:UserPreferredOrganization')
                    ->getPreferredOrganization($user, $organization);
                $this->user = $user;
            }
            if (is_object($this->preferredOrg) && $this->preferredOrg->getId()) {
                $id = $this->preferredOrg->getId();
            }
        }

        return $id;
    }
}
