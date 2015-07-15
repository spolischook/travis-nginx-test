<?php

namespace OroPro\Bundle\EwsBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProEwsBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->addEwsFieldsToOroEmailOriginTable($schema);
        $this->createOroEmailFolderEwsTable($schema);
        $this->createOroEmailEwsTable($schema);

        /** Foreign keys generation **/
        $this->addOroEmailFolderEwsForeignKeys($schema);
        $this->addOroEmailEwsForeignKeys($schema);
    }

    /**
     * Add EWS fields to the oro_email_origin table
     *
     * @param Schema $schema
     */
    protected function addEwsFieldsToOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('ews_server', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ews_user_email', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create oro_email_folder_ews table
     *
     * @param Schema $schema
     */
    protected function createOroEmailFolderEwsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_folder_ews');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', []);
        $table->addColumn('ews_id', 'string', ['length' => 255]);
        $table->addColumn('ews_change_key', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_6622DD60162CB942');
    }

    /**
     * Create oro_email_ews table
     *
     * @param Schema $schema
     */
    protected function createOroEmailEwsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_ews');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('ews_id', 'string', ['length' => 255]);
        $table->addColumn('ews_change_key', 'string', ['length' => 255]);
        $table->addColumn('ews_folder_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_1257C23FA832C1C9');
        $table->addIndex(['ews_id'], 'idx_oro_email_ews', []);
        $table->addIndex(['ews_folder_id'], 'IDX_1257C23F9380036F');
    }

    /**
     * Add oro_email_folder_ews foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroEmailFolderEwsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder_ews');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_ews foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroEmailEwsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_ews');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder_ews'),
            ['ews_folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null],
            'FK_1257C23F9380036F'
        );
    }
}
