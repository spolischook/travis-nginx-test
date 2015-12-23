<?php

namespace OroPro\Bundle\UserBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Class UserProHelper.
 */
class UserProHelper
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns true if user assigned to global organization.
     *
     * @param User|null $user If empty, user from current token will be used.
     * @return bool
     */
    public function isUserAssignedToGlobalOrganization(User $user = null)
    {
        $organizations = $this->getUser($user)->getOrganizations();
        $isAssigned = $organizations->exists(
            function ($key, Organization $organization) {
                return $organization->getIsGlobal();
            }
        );

        return $isAssigned;
    }

    /**
     * Returns true if user assigned to organization.
     *
     * @param Organization $organization
     * @param User|null $user If empty, user from current token will be used.
     * @return bool
     */
    public function isUserAssignedToOrganization(Organization $organization, User $user = null)
    {
        $organizations = $this->getUser($user)->getOrganizations();
        $isAssigned = $organizations->exists(
            function ($key, $currentOrganization) use ($organization) {
                /** @var Organization $organization */
                return ($currentOrganization->getId() === $organization->getId());
            }
        );

        return $isAssigned;
    }

    /**
     * @param User $user|null
     * @return User $user
     */
    protected function getUser(User $user = null)
    {
        if ($user) {
            return $user;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new \RuntimeException('Security token in token storage must exist.');
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException(
                sprintf(
                    'Security token must return a user object instance of %s, %s is given.',
                    'Oro\Bundle\UserBundle\Entity\User',
                    is_object($user) ? get_class($user) : gettype($user)
                )
            );
        }

        return $user;
    }
}
