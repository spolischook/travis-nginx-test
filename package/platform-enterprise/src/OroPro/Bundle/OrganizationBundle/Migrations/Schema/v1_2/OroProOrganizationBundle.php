<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProOrganizationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_organization');
        $table->addColumn(
            'is_global',
            'boolean',
            [
                OroOptions::KEY => [
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'form' => ['form_type' => 'oropro_organization_is_global'],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_TRUE],
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY
                ]
            ]
        );
    }
}
