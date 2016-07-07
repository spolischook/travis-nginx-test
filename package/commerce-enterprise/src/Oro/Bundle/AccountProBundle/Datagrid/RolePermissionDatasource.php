<?php

namespace Oro\Bundle\AccountProBundle\Datagrid;

use OroB2B\Bundle\AccountBundle\Datagrid\RolePermissionDatasource as BaseRolePermissionDatasource;

class RolePermissionDatasource extends BaseRolePermissionDatasource
{
    /** @var array|string[] */
    protected static $excludePermissions = [];
}
