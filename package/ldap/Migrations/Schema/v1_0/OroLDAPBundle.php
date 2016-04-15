<?php

namespace OroCRMPro\Bundle\LDAPBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLDAPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addLdapDistinguishedNamesColumn($schema);
        self::addLdapTransportColumns($schema);
        self::setUserFieldsImportExportConfiguration($schema);
    }

    /**
     * Add ldap_distinguished_names column to oro_user table.
     *
     * @param Schema $schema
     */
    public static function addLdapDistinguishedNamesColumn(Schema $schema)
    {
        $userTable = $schema->getTable('oro_user');
        $userTable->addColumn(
            'ldap_distinguished_names',
            'array',
            [
                'oro_options' => [
                    'extend'       => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'form'         => ['is_enabled' => false],
                    'datagrid'     => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'importexport' => ['excluded' => true],
                ],
                'notnull'     => false,
            ]
        );
    }

    /**
     * Add LDAP transport columns to oro_integration_transport table.
     *
     * @param Schema $schema
     */
    public static function addLdapTransportColumns(Schema $schema)
    {
        $transportTable = $schema->getTable('oro_integration_transport');
        $transportTable->addColumn('ldap_host', 'string', ['notnull' => false]);
        $transportTable->addColumn('ldap_port', 'integer', ['notnull' => false]);
        $transportTable->addColumn('ldap_encryption', 'string', ['notnull' => false]);
        $transportTable->addColumn('ldap_base_dn', 'string', ['notnull' => false]);
        $transportTable->addColumn('ldap_username', 'string', ['notnull' => false]);
        $transportTable->addColumn('ldap_password', 'string', ['notnull' => false]);
        $transportTable->addColumn('ldap_account_domain', 'string', ['notnull' => false]);
        $transportTable->addColumn('ldap_account_domain_short', 'string', ['notnull' => false]);
    }

    /**
     * Set fields excluded from importexport of Users.
     *
     * @param Schema $schema
     */
    public static function setUserFieldsImportExportConfiguration(Schema $schema)
    {
        $table = $schema->getTable('oro_user');

        $table->getColumn('id')->setOptions(
            [OroOptions::KEY => ['importexport' => ['excluded' => true]]]
        );
        $table->getColumn('login_count')->setOptions(
            [OroOptions::KEY => ['importexport' => ['excluded' => true]]]
        );
        $table->getColumn('createdAt')->setOptions(
            [OroOptions::KEY => ['importexport' => ['excluded' => true]], 'type' => Type::getType(Type::DATETIME)]
        );
        $table->getColumn('updatedAt')->setOptions(
            [OroOptions::KEY => ['importexport' => ['excluded' => true]], 'type' => Type::getType(Type::DATETIME)]
        );
    }
}
