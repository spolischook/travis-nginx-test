<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPriceListChangeTriggerTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BPriceListChangeTriggerTable($schema);
        $this->addOroB2BPriceListChangeTriggerForeignKeys($schema);
    }

    /**
     * Create orob2b_price_list_change_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroB2BPriceListChangeTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_ch_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_price_list_change_trigger foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BPriceListChangeTriggerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_ch_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
