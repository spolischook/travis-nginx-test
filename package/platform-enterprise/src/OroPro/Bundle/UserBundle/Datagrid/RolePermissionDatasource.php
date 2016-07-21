<?php

namespace OroPro\Bundle\UserBundle\Datagrid;

use Oro\Bundle\UserBundle\Datagrid\RolePermissionDatasource as BaseRolePermissionDatasource;

class RolePermissionDatasource extends BaseRolePermissionDatasource
{
    /** @var string[] */
    protected static $excludePermissions = [];
}
