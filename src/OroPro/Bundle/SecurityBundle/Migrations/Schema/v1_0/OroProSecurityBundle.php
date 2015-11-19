<?php

namespace OroPro\Bundle\SecurityBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProSecurityBundle implements Migration
{
    protected static $entitiesShareScopesConfig = [
        'oro_tracking_website',
        'orocrm_account',
        'orocrm_call',
        'orocrm_campaign',
        'orocrm_campaign_email',
        'orocrm_case',
        'orocrm_contact',
        'orocrm_contactus_request',
        'orocrm_marketing_list',
        'orocrm_sales_lead',
        'orocrm_sales_opportunity',
        'orocrm_sales_funnel',
        'orocrm_task',
    ];

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateAclTables($schema);
    }

    /**
     * Updates acl tables.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateAclTables(Schema $schema)
    {
        // remove platform-depended sql parts, for example "ON UPDATE CASCADE" for MySql
        $aclEntriesTable = $schema->getTable('acl_entries');
        // additional column, which duplicates acl_object_identities.object_identifier field.
        $aclEntriesTable->addColumn(
            'record_id',
            'bigint',
            [
                'unsigned' => true,
                'notnull' => false,
            ]
        );
        $aclEntriesTable->removeForeignKey('FK_46C8B806DF9183C9');
        $aclEntriesTable->addForeignKeyConstraint(
            $schema->getTable('acl_security_identities'),
            ['security_identity_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_46C8B806DF9183C9'
        );


        $aclEntriesTable->removeForeignKey('FK_46C8B8063D9AB4A6');
        $aclEntriesTable->addForeignKeyConstraint(
            $schema->getTable('acl_object_identities'),
            ['object_identity_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_46C8B8063D9AB4A6'
        );

        $aclEntriesTable->removeForeignKey('FK_46C8B806EA000B10');
        $aclEntriesTable->addForeignKeyConstraint(
            $schema->getTable('acl_classes'),
            ['class_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_46C8B806EA000B10'
        );

        $aclObjectIdentityAncestorsTable = $schema->getTable('acl_object_identity_ancestors');
        $aclObjectIdentityAncestorsTable->removeForeignKey('FK_825DE299C671CEA1');
        $aclObjectIdentityAncestorsTable->addForeignKeyConstraint(
            $schema->getTable('acl_object_identities'),
            ['ancestor_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_825DE299C671CEA1'
        );

        $aclObjectIdentityAncestorsTable->removeForeignKey('FK_825DE2993D9AB4A6');
        $aclObjectIdentityAncestorsTable->addForeignKeyConstraint(
            $schema->getTable('acl_object_identities'),
            ['object_identity_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_825DE2993D9AB4A6'
        );

        $aclSecurityIdentityTable = $schema->getTable('acl_security_identities');
        $aclSecurityIdentityTable->addIndex(['username'], 'acl_sids_username_idx');
    }
}
