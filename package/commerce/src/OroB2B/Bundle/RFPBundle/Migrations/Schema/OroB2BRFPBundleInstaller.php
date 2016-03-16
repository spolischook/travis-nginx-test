<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BRFPBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

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
        $this->createOroRfpAssignedAccUsersTable($schema);
        $this->createOroRfpAssignedUsersTable($schema);
        $this->createOroB2BRfpRequestTable($schema);
        $this->createOroB2BRfpStatusTable($schema);
        $this->createOroB2BRfpStatusTranslationTable($schema);
        $this->createOrob2BRfpRequestProductTable($schema);
        $this->createOrob2BRfpRequestProductItemTable($schema);

        /** Foreign keys generation **/
        $this->addOroRfpAssignedAccUsersForeignKeys($schema);
        $this->addOroRfpAssignedUsersForeignKeys($schema);
        $this->addOroB2BRfpRequestForeignKeys($schema);
        $this->addOroB2BRfpStatusForeignKeys($schema);
        $this->addOrob2BRfpRequestProductForeignKeys($schema);
        $this->addOrob2BRfpRequestProductItemForeignKeys($schema);

        $this->addNoteAssociations($schema, $this->noteExtension);
        $this->addActivityAssociations($schema, $this->activityExtension);
    }

    /**
     * Create oro_rfp_assigned_acc_users table
     *
     * @param Schema $schema
     */
    protected function createOroRfpAssignedAccUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rfp_assigned_acc_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('account_user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'account_user_id']);
    }

    /**
     * Create oro_rfp_assigned_users table
     *
     * @param Schema $schema
     */
    protected function createOroRfpAssignedUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rfp_assigned_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'user_id']);
    }

    /**
     * Create orob2b_rfp_request table
     *
     * @param Schema $schema
     */
    protected function createOroB2BRfpRequestTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('status_id', 'integer', ['notnull' => false]);
        $table->addColumn('cancellation_reason', 'text', ['notnull' => false]);
        $table->addColumn('first_name', 'string', ['length' => 255]);
        $table->addColumn('last_name', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('phone', 'string', ['length' => 255]);
        $table->addColumn('company', 'string', ['length' => 255]);
        $table->addColumn('role', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('note', 'text', []);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('deleted_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_rfp_status table
     *
     * @param Schema $schema
     */
    protected function createOroB2BRfpStatusTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['notnull' => false]);
        $table->addColumn('deleted', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'orob2b_rfp_status_name_idx', []);
    }

    /**
     * Create orob2b_rfp_status_translation table
     *
     * @param Schema $schema
     */
    protected function createOroB2BRfpStatusTranslationTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_status_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_id', 'field'], 'orob2b_rfp_status_trans_idx', []);
    }

    /**
     * Create orob2b_rfp_request_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_rfp_request_prod_item table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestProductItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request_prod_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('request_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn(
            'value',
            'money',
            [
                'notnull' => false,
                'precision' => 19,
                'scale' => 4,
                'comment' => '(DC2Type:money)',
            ]
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_rfp_assigned_acc_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRfpAssignedAccUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_assigned_acc_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_rfp_assigned_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRfpAssignedUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_assigned_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_request foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BRfpRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_status'),
            ['status_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_status_translation foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BRfpStatusForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_status_translation');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_status'),
            ['object_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_request_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_request_prod_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestProductItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request_prod_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request_product'),
            ['request_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Enable notes for RFP entity
     *
     * @param Schema $schema
     * @param NoteExtension $noteExtension
     */
    protected function addNoteAssociations(Schema $schema, NoteExtension $noteExtension)
    {
        $noteExtension->addNoteAssociation($schema, 'orob2b_rfp_request');
    }

    /**
     * Enables Email activity for RFP entity
     *
     * @param Schema $schema
     * @param ActivityExtension $activityExtension
     */
    protected function addActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $activityExtension->addActivityAssociation($schema, 'oro_email', 'orob2b_rfp_request');
        $activityExtension->addActivityAssociation($schema, 'oro_calendar_event', 'orob2b_rfp_request');
    }
}
