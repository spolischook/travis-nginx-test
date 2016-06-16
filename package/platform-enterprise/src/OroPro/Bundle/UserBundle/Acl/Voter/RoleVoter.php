<?php

namespace OroPro\Bundle\UserBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;
use OroPro\Bundle\UserBundle\Helper\UserProHelper;

/**
 * Class RoleVoter. Next permission logic is implemented:
 * - user logged in to global organization can view/edit/delete any roles;
 * - user can view/edit/delete role with assigned organization if he is logged in to it;
 * - user can view/edit/delete roles without organization if there is no global organization
 *   or he is assigned to system organization.
 */
class RoleVoter implements VoterInterface
{
    const VIEW = 'VIEW';
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';

    /**
     * @var UserProHelper
     */
    protected $userHelper;

    /**
     * @var OrganizationProHelper
     */
    protected $organizationHelper;

    /**
     * @param UserProHelper $userProHelper
     * @param OrganizationProHelper $organizationHelper
     */
    public function __construct(UserProHelper $userProHelper, OrganizationProHelper $organizationHelper)
    {
        $this->userHelper = $userProHelper;
        $this->organizationHelper = $organizationHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return 'Oro\Bundle\UserBundle\Entity\Role' === $class ||
            in_array('Oro\Bundle\UserBundle\Entity\Role', class_parents($class));
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, [self::DELETE, self::EDIT, self::VIEW], true);
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        $objectClass = ClassUtils::getClass($object);
        if (!$this->supportsClass($objectClass)) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                return self::ACCESS_ABSTAIN;
            }
        }

        $result = $this->checkOrganizationContext($token, $object);

        return $result;
    }

    /**
     * @param TokenInterface $token
     * @param Role $role
     * @return int
     */
    protected function checkOrganizationContext(TokenInterface $token, Role $role)
    {
        if (!$token instanceof OrganizationContextTokenInterface) {
            return self::ACCESS_ABSTAIN;
        }

        /** @var User $currentUser */
        $currentUser = $token->getUser();

        /** @var Organization $currentOrganization */
        $currentOrganization = $token->getOrganizationContext();

        // User logged in to global organization can view/edit/delete any roles
        $isUserAssignedToGlobalOrganization = $this->userHelper->isUserAssignedToGlobalOrganization($currentUser);
        $provideAccess = $isUserAssignedToGlobalOrganization && $currentOrganization->getIsGlobal();

        if ($provideAccess) {
            return self::ACCESS_ABSTAIN;
        }

        /** @var Organization $roleOrganization */
        $roleOrganization = $role->getOrganization();
        if ($roleOrganization) {
            // User can view/edit/delete role with assigned organization if he is logged in to it
            $provideAccess = $this->userHelper->isUserAssignedToOrganization($roleOrganization, $currentUser) &&
                $roleOrganization->getId() == $currentOrganization->getId();
        } else {
            // User can view/edit/delete roles without organization if there is no global organization
            // or he is assigned to system organization
            $provideAccess = $isUserAssignedToGlobalOrganization ||
                !$this->organizationHelper->isGlobalOrganizationExists();
        }

        if ($provideAccess) {
            return self::ACCESS_ABSTAIN;
        }

        return self::ACCESS_DENIED;
    }
}
