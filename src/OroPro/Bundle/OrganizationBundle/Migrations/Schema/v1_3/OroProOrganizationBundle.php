<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Schema\v1_3;

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
        $this->createOroProOrganizationUserTable($schema);
    }

    /**
     * Create oro_pro_organization_user table
     *
     * @param Schema $schema
     */
    public static function createOroProOrganizationUserTable(Schema $schema)
    {
        $table = $schema->createTable('oro_pro_organization_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addIndex(['user_id'], 'IDX_AA377654A76ED395');
        $table->addIndex(['organization_id'], 'IDX_AA37765432C8A3DE');
        $table->addUniqueIndex(['user_id', 'organization_id'], 'UNQ_pro_user_organization');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            'FK_AA377654A76ED395'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            'FK_AA37765432C8A3DE'
        );
    }
}
