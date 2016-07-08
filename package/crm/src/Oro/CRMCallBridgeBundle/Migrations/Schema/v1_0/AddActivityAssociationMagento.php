<?php

namespace Oro\CRMCallBridgeBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;

class AddActivityAssociationMagento implements
    Migration,
    OrderedMigrationInterface,
    ActivityExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addActivityAssociations($schema, $this->activityExtension);
    }

    /**
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    public static function addActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $activityAssociationTables = [
            'orocrm_magento_customer',
            'orocrm_magento_order',
            'orocrm_magento_cart'
        ];

        foreach ($activityAssociationTables as $tblName) {
            $associationTableName = $activityExtension->getAssociationTableName('orocrm_call', $tblName);
            if (!$schema->hasTable($associationTableName)) {
                $activityExtension->addActivityAssociation($schema, 'orocrm_call', $tblName);
            }
        }
    }
}
