<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_item');
        $table->addColumn('entity_class', 'string', ['length' => 255, 'notnull' => false]);
        $table->changeColumn('entity_id', ['string', 'length' => 255, 'notnull' => false]);
    }
}
