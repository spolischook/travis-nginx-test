<?php

namespace OroPro\Bundle\EwsBundle\Migrations\Schema\v1_2;

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

        $table->addColumn('ews_folder_id', 'integer', ['notnull' => true]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder_ews'),
            ['ews_folder_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null],
            'FK_1257C23F9380036F'
        );
        $table->addIndex(['ews_folder_id'], 'IDX_1257C23F9380036F');
    }
}
