<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroPro\Bundle\OrganizationConfigBundle\Migrations\Schema\v1_0\UpdateEmailTemplates;

class OroProOrganizationConfigBundleInstaller implements Installation
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
        $migration = new UpdateEmailTemplates();
        $migration->up($schema, $queries);
    }
}
