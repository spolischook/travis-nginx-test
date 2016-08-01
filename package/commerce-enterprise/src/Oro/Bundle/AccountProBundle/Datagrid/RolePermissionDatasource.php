<?php

namespace Oro\Bundle\AccountProBundle\Datagrid;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

use OroB2B\Bundle\AccountBundle\Datagrid\RolePermissionDatasource as BaseRolePermissionDatasource;

class RolePermissionDatasource extends BaseRolePermissionDatasource
{
    /** @var array */
    protected $excludePermissions = [];

    /**
     * @param string $permissionName
     */
    public function addExcludePermission($permissionName)
    {
        $this->excludePermissions[] = $permissionName;
    }

    /**
     * {@inheritdoc}
     */
    protected function preparePermissions(AclPrivilege $privilege, $item)
    {
        $data = parent::preparePermissions($privilege, $item);
        $data['permissions'] = array_filter(
            $data['permissions'],
            function (array $permission) {
                return $this->isSupportedPermission($permission['name']);
            }
        );

        return $data;
    }

    /**
     * @param string $permissionName
     * @return bool
     */
    protected function isSupportedPermission($permissionName)
    {
        return !in_array($permissionName, $this->excludePermissions, true);
    }
}
