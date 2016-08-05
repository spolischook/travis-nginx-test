<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountProBundleInstaller implements Installation
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
     *
     * Now installer is empty, because do not needed any manipulations with db on install, but on platform update
     * we should remove old uexcess permission SHARE
     */
    public function up(Schema $schema, QueryBag $queries)
    {
    }
}
