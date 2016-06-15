<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

class AclPrivilegeProRepository extends AclPrivilegeRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getAclPermission(AclExtensionInterface $extension, $permission, $mask, AclPrivilege $privilege)
    {
        return new AclPermission(
            $permission,
            $extension->getAccessLevel(
                $mask,
                $permission
            )
        );
    }
}
