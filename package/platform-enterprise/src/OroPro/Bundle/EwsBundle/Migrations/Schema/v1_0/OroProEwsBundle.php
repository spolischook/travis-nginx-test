<?php

namespace OroPro\Bundle\EwsBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProEwsBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroEmailFolderEwsTable($schema);
        self::oroEmailEwsTable($schema);
        self::updateOroEmailOriginTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailFolderEwsTable(Schema $schema)
    {
        /** Create table */
        $table = $schema->createTable('oro_email_folder_ews');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', []);
        $table->addColumn('ews_id', 'string', ['length' => 255]);
        $table->addColumn('ews_change_key', 'string', ['length' => 255]);

        /** Create indexes and keys */
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_6622DD60162CB942');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @param bool   $isEmailIdUniqueIndex
     */
    public static function oroEmailEwsTable(Schema $schema, $isEmailIdUniqueIndex = true)
    {
        /** Create table */
        $table = $schema->createTable('oro_email_ews');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('ews_id', 'string', ['length' => 255]);
        $table->addColumn('ews_change_key', 'string', ['length' => 255]);

        /** Create indexes and keys */
        $table->setPrimaryKey(['id']);

        if ($isEmailIdUniqueIndex) {
            $table->addUniqueIndex(['email_id'], 'UNIQ_1257C23FA832C1C9');
        } else {
            $table->addIndex(['email_id'], 'IDX_1257C23FA832C1C9');
        }

        $table->addIndex(['ews_id'], 'idx_oro_email_ews', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    public static function updateOroEmailOriginTable(Schema $schema)
    {
        /** Add EWS fields to the oro_email_origin table **/
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('ews_server', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ews_user_email', 'string', ['notnull' => false, 'length' => 255]);
    }
}
