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
        /** Generate table oro_email_folder_ews **/
        $table = $schema->createTable('oro_email_folder_ews');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', []);
        $table->addColumn('ews_id', 'string', ['length' => 255]);
        $table->addColumn('ews_change_key', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['folder_id'], 'UNIQ_6622DD60162CB942');
        /** End of generate table oro_email_folder_ews **/

        /** Generate table oro_email_ews **/
        $table = $schema->createTable('oro_email_ews');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('ews_id', 'string', ['length' => 255]);
        $table->addColumn('ews_change_key', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email_id'], 'UNIQ_1257C23FA832C1C9');
        /** End of generate table oro_email_ews **/

        /** Generate foreign keys for table oro_email_folder_ews **/
        $table = $schema->getTable('oro_email_folder_ews');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_email_folder_ews **/

        /** Generate foreign keys for table oro_email_ews **/
        $table = $schema->getTable('oro_email_ews');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_email_ews **/

        /** Add EWS fields to the oro_email_origin table **/
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('ews_server', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ews_user_email', 'string', ['notnull' => false, 'length' => 255]);
    }
}
