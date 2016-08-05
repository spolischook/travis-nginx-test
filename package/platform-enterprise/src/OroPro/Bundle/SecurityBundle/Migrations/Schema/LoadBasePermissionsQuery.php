<?php

namespace OroPro\Bundle\SecurityBundle\Migrations\Schema;

use Oro\Bundle\SecurityBundle\Migrations\Schema\LoadBasePermissionsQuery as BaseLoadBasePermissionsQuery;

class LoadBasePermissionsQuery extends BaseLoadBasePermissionsQuery
{
    /** @var array */
    protected $permissions = [
        'SHARE'
    ];
}
