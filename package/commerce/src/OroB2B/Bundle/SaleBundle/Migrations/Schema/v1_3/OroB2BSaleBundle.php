<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroQuoteAssignedAccUsersTable($schema);
        $this->createOroQuoteAssignedUsersTable($schema);

        /** Foreign keys generation **/
        $this->addOroQuoteAssignedAccUsersForeignKeys($schema);
        $this->addOroQuoteAssignedUsersForeignKeys($schema);
    }

    /**
     * Create oro_quote_assigned_acc_users table
     *
     * @param Schema $schema
     */
    protected function createOroQuoteAssignedAccUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_quote_assigned_acc_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('account_user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'account_user_id']);
    }

    /**
     * Create oro_quote_assigned_users table
     *
     * @param Schema $schema
     */
    protected function createOroQuoteAssignedUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_quote_assigned_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'user_id']);
    }

    /**
     * Add oro_quote_assigned_acc_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroQuoteAssignedAccUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_assigned_acc_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_quote_assigned_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroQuoteAssignedUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_assigned_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
