<?php

namespace OroPro\Bundle\EwsBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProEwsBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_ews');

        $table->dropIndex('UNIQ_1257C23FA832C1C9');
        $table->addIndex(['email_id'], 'IDX_1257C23FA832C1C9');
    }
}
