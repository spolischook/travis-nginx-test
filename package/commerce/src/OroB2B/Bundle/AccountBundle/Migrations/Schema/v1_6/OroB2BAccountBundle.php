<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addAccountUserSettingsTable($schema);

        $table = $schema->getTable('orob2b_account_user_role');
        $table->addColumn('self_managed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('public', 'boolean', ['notnull' => true, 'default' => true]);

        $this->updateAccountUserRoles($queries);
        $this->removeAccountAddressSerializedDataColumn($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    protected function addAccountUserSettingsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_settings');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3]);

        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_account_user_id'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_website_id'
        );

        $table->addUniqueIndex(['account_user_id', 'website_id'], 'unique_acc_user_website');
    }

    /**
     * @param QueryBag $queries
     */
    protected function updateAccountUserRoles(QueryBag $queries)
    {
        $anonymousRoleName = 'IS_AUTHENTICATED_ANONYMOUSLY';

        $queries->addPostQuery(
            "UPDATE orob2b_account_user_role SET self_managed = TRUE WHERE role <> '$anonymousRoleName'"
        );
        $queries->addPostQuery(
            "UPDATE orob2b_account_user_role SET public = FALSE WHERE role = '$anonymousRoleName'"
        );
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function removeAccountAddressSerializedDataColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_address');
        if ($table->hasColumn('serialized_data') &&
            !class_exists('Oro\Bundle\EntitySerializedFieldsBundle\OroEntitySerializedFieldsBundle')
        ) {
            $table->dropColumn('serialized_data');
        }
    }
}
