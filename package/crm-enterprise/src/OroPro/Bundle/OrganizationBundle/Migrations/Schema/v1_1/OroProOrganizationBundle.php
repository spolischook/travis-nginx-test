<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProOrganizationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroProOrganizationUserPrefTable($schema);

        /** Foreign keys generation **/
        $this->addOroProOrganizationUserPrefForeignKeys($schema);
    }

    /**
     * Create oro_pro_organization_user_pref table
     *
     * @param Schema $schema
     */
    protected function createOroProOrganizationUserPrefTable(Schema $schema)
    {
        $table = $schema->createTable('oro_pro_organization_user_pref');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_A7BD91732C8A3DE', []);

        $table->addUniqueIndex(['user_id'], 'UNIQ_A7BD917A76ED395');
    }

    /**
     * Add oro_pro_organization_user_pref foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProOrganizationUserPrefForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_pro_organization_user_pref');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            'FK_A7BD91732C8A3DE'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            'FK_A7BD917A76ED395'
        );
    }
}
