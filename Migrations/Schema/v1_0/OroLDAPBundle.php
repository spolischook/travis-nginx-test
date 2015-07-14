<?php

namespace Oro\Bundle\LDAPBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
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
                    'extend'       => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'form'         => ['is_enabled' => false],
                    'datagrid'     => ['is_visible' => false],
                    'importexport' => ['excluded' => true],
                    'view'         => ['acl' => 'oro_integration_update'],
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
        $transportTable->addColumn('oro_ldap_host', 'string', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_port', 'integer', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_encryption', 'string', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_base_dn', 'string', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_username', 'string', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_password', 'string', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_acc_domain', 'string', ['notnull' => false]);
        $transportTable->addColumn('oro_ldap_acc_domain_short', 'string', ['notnull' => false]);
    }
}
