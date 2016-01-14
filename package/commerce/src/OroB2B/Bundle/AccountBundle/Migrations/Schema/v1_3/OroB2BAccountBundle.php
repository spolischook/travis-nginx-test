<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    const ORO_USER_TABLE_NAME = 'oro_user';

    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_USER_TABLE_NAME = 'orob2b_account_user';

    const ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME = 'orob2b_account_sales_reps';
    const ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME = 'orob2b_account_user_sales_reps';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BAccountSalesRepresentativesTable($schema);
        $this->createOroB2BAccountUserSalesRepresentativesTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAccountSalesRepresentativesForeignKeys($schema);
        $this->addOroB2BAccountUserSalesRepresentativesForeignKeys($schema);
    }

    /**
     * Create orob2b_account_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_id', 'user_id']);
    }

    /**
     * Create orob2b_account_user_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_user_id', 'user_id']);
    }

    /**
     * Add orob2b_account_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_user_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
