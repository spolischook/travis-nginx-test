<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProOrganizationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->addFieldsToOroOrganizationTable($schema);
        $this->createOroProOrganizationUserTable($schema);
        $this->createOroProOrganizationUserPrefTable($schema);

        /** Foreign keys generation **/
        $this->addOroProOrganizationUserForeignKeys($schema);
        $this->addOroProOrganizationUserPrefForeignKeys($schema);
    }

    /**
     * Add fields to oro_organization table
     *
     * @param Schema $schema
     */
    protected function addFieldsToOroOrganizationTable(Schema $schema)
    {
        $table = $schema->getTable('oro_organization');
        $table->addColumn(
            'is_global',
            'boolean',
            [
                OroOptions::KEY => [
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'form' => ['form_type' => 'oropro_organization_is_global'],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_TRUE],
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY
                ]
            ]
        );
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
     * Add oro_pro_organization_user foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProOrganizationUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_pro_organization_user');
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
