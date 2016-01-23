<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::oroEmailUserTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailUserTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addColumn('unsyncedFlagCount', 'integer', ['default' => '0']);
    }
}
