<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityExtension implements ExtendExtensionAwareInterface
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
     * Adds the association between the given table and the table contains activity records
     *
     * The activity entity must be included in 'activity' group ('groups' attribute of 'grouping' scope)
     *
     * @param Schema $schema
     * @param string $activityTableName Activity entity table name. It is owning side of the association
     * @param string $targetTableName   Target entity table name
     * @param bool   $immutable         Set TRUE to prohibit disabling the activity association from UI
     */
    public function addActivityAssociation(
        Schema $schema,
        $activityTableName,
        $targetTableName,
        $immutable = false
    ) {
        $targetTable = $schema->getTable($targetTableName);

        // Column names are used to show a title of target entity
        $targetTitleColumnNames = $targetTable->getPrimaryKeyColumns();
        // Column names are used to show detailed info about target entity
        $targetDetailedColumnNames = $targetTable->getPrimaryKeyColumns();
        // Column names are used to show target entity in a grid
        $targetGridColumnNames = $targetTable->getPrimaryKeyColumns();

        $activityClassName = $this->extendExtension->getEntityClassByTableName($activityTableName);

        $options = new OroOptions();
        $options->append(
            'activity',
            'activities',
            $activityClassName
        );
        if ($immutable) {
            $options->append(
                'activity',
                'immutable',
                $activityClassName
            );
        }

        $targetTable->addOption(OroOptions::KEY, $options);

        $associationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTableName),
            ActivityScope::ASSOCIATION_KIND
        );

        $this->extendExtension->addManyToManyRelation(
            $schema,
            $activityTableName,
            $associationName,
            $targetTable,
            $targetTitleColumnNames,
            $targetDetailedColumnNames,
            $targetGridColumnNames,
            [
                'extend' => [
                    'without_default' => true
                ]
            ]
        );
    }
}
