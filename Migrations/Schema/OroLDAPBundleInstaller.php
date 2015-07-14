<?php

namespace Oro\Bundle\LDAPBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\LDAPBundle\Migrations\Schema\v1_0\OroLDAPBundle as OroLDAPBundle_v1_0;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

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
    }
}
