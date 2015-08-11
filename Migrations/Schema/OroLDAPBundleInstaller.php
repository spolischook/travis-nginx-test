<?php

namespace OroCRMPro\Bundle\LDAPBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroCRMPro\Bundle\LDAPBundle\Migrations\Schema\v1_0\OroLDAPBundle as OroLDAPBundle_v1_0;

class OroLDAPBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroLDAPBundle_v1_0::addLdapDistinguishedNamesColumn($schema);
        OroLDAPBundle_v1_0::addLdapTransportColumns($schema);
        OroLDAPBundle_v1_0::setUserFieldsImportExportConfiguration($schema);
    }
}
