<?php

namespace OroPro\Bundle\OrganizationBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

use Oro\Bundle\EntityBundle\Grid\DynamicFieldsExtension as DynamicFields;

class DynamicFieldsExtension extends DynamicFields
{
    /**
     * {@inheritdoc}
     */
    protected function getFields(DatagridConfiguration $config)
    {
        parent::getFields($config);

//        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
//        if (!$this->configManager->hasConfig($entityClassName)) {
//            return [];
//        }
//
//        $entityConfigProvider   = $this->configManager->getProvider('entity');
//        $extendConfigProvider   = $this->configManager->getProvider('extend');
//        $datagridConfigProvider = $this->configManager->getProvider('datagrid');
//
//        $fields   = [];
//        $fieldIds = $entityConfigProvider->getIds($entityClassName);
//        foreach ($fieldIds as $fieldId) {
//            $extendConfig = $extendConfigProvider->getConfigById($fieldId);
//            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
//                && $datagridConfigProvider->getConfigById($fieldId)->is('is_visible')
//                && !$extendConfig->is('state', ExtendScope::STATE_NEW)
//                && !$extendConfig->is('is_deleted')
//            ) {
//                $fields[] = $fieldId;
//            }
//        }
//
//        return $fields;
    }
}
