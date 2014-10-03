<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityConfigBundle\Twig\DynamicFieldsExtension as DynamicFields;

class DynamicFieldsExtension extends DynamicFields
{
    /**
     * {@inheritdoc}
     */
    public function filterFields(ConfigInterface $config)
    {
        parent::filterFields($config);

//        $extendConfig = $this->extendProvider->getConfigById($config->getId());
//        /** @var FieldConfigId $fieldConfigId */
//        $fieldConfigId = $extendConfig->getId();
//
//        // skip system, new and deleted fields
//        if (!$config->is('owner', ExtendScope::OWNER_CUSTOM)
//            || $config->is('state', ExtendScope::STATE_NEW)
//            || $config->is('is_deleted')
//        ) {
//            return false;
//        }
//
//        // skip invisible fields
//        if (!$this->viewProvider->getConfigById($config->getId())->is('is_displayable')) {
//            return false;
//        }
//
//        // skip relations if they are referenced to deleted entity
//        $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType());
//        if (in_array($underlyingFieldType, array('oneToMany', 'manyToOne', 'manyToMany'))
//            && $this->extendProvider->getConfig($extendConfig->get('target_entity'))->is('is_deleted', true)
//        ) {
//            return false;
//        }
//
//        return true;
    }
}
