<?php

namespace Oro\Bundle\LDAPBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLDAPBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $userTable = $schema->getTable('oro_user');
        $userTable->addColumn('ldap_mappings', 'array', [
            'oro_options' => [
                'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                'form' => ['is_enabled' => false],
                'datagrid' => ['is_visible' => false],
                'view' => ['acl' => 'oro_integration_update'],
            ],
            'notnull' => false
        ]);

        $transportTable = $schema->getTable('oro_integration_transport');
        $transportTable->addColumn('oro_ldap_server_hostname', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_server_port', 'integer', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_server_encryption', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_server_base_dn', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_admin_dn', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_admin_password', 'string', [
            'notnull' => false
        ]);
    }
}
