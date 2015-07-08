<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;

class RoleVoter implements VoterInterface
{
    const VIEW = 'VIEW';
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return 'Oro\Bundle\UserBundle\Entity\Role' === $class;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, [self::DELETE, self::EDIT, self::VIEW]);
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
     * @param Role $object
     * @return int
     */
    protected function checkOrganizationContext(TokenInterface $token, Role $object)
    {
        /** @var User $user */
        $user = $token->getUser();

        $userOrganizations = $user->getOrganizations();
        $provideAccess = $userOrganizations->exists(
            function($key, $organization) {
                return (true == $organization->getIsGlobal());
            }
        );

        if ($provideAccess) {
            return self::ACCESS_GRANTED;
        }

        $roleOrganization = $object->getOrganization();

        if ($roleOrganization) {
            $roleOrganizationId = $roleOrganization->getId();

            $provideAccess = $userOrganizations->exists(
                function($key, $organization) use ($roleOrganizationId) {
                    return ($organization->getId() === $roleOrganizationId);
                }
            );
        }

        if ($provideAccess) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }
}
