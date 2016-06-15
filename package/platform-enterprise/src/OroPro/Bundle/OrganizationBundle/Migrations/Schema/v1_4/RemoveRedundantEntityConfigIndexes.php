<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class RemoveRedundantEntityConfigIndexes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_index_value WHERE scope=:scope and code=:code',
                ['scope' => 'organization', 'code' => 'applicable'],
                ['scope' => 'string', 'code' => 'string']
            )
        );
    }
}
