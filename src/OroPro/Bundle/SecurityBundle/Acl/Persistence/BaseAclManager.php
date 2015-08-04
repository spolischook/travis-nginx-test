<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Persistence;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidInterface;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class BaseAclManager implements AclSidInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSid($identity)
    {
        if (is_string($identity)) {
            return new RoleSecurityIdentity($identity);
        } elseif ($identity instanceof RoleInterface) {
            return new RoleSecurityIdentity($identity->getRole());
        } elseif ($identity instanceof UserInterface) {
            return UserSecurityIdentity::fromAccount($identity);
        } elseif ($identity instanceof TokenInterface) {
            return UserSecurityIdentity::fromToken($identity);
        } elseif ($identity instanceof BusinessUnitInterface) {
            return BusinessUnitSecurityIdentity::fromBusinessUnit($identity);
        } elseif ($identity instanceof OrganizationInterface) {
            return OrganizationSecurityIdentity::fromOrganization($identity);
        }

        throw new \InvalidArgumentException(
            sprintf(
                '$identity must be a string or implement one of RoleInterface, UserInterface, TokenInterface,'
                . ' BusinessUnitInterface, OrganizationInterface (%s given)',
                is_object($identity) ? get_class($identity) : gettype($identity)
            )
        );
    }
}
