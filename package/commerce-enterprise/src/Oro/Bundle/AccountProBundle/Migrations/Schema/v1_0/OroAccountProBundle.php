<?php

namespace Oro\Bundle\AccountProBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\RemovePermissionGroupNames;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class OroAccountProBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new RemovePermissionGroupNames(['SHARE'], [AccountUser::SECURITY_GROUP]));
    }
}
