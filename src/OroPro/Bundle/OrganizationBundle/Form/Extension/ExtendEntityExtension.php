<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Form\Type\CustomEntityType;

class ExtendEntityExtension extends AbstractTypeExtension
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['dynamic_fields_disabled']) {
            return;
        }

        $name = $builder instanceof FormConfigBuilder ? $builder->getName() : $builder->getForm()->getName();
        if ($name == CustomEntityType::NAME || empty($options['data_class'])) {
            return;
        }

        $className = $options['data_class'];
        if (!$this->configManager->getProvider('extend')->hasConfig($className)) {
            return;
        }

        if (!$this->hasActiveFields($className)) {
            return;
        }

        $builder->add(
            'additional',
            CustomEntityType::NAME,
            array(
                'inherit_data' => true,
                'class_name' => $className
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'dynamic_fields_disabled' => false,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function hasActiveFields($className)
    {
        // TODO: Convert this method to separate helper service and reuse it in CustomEntityType,
        // TODO: should be done in scope of https://magecore.atlassian.net/browse/BAP-1721
        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->configManager->getProvider('extend');
        /** @var ConfigProvider $formConfigProvider */
        $formConfigProvider = $this->configManager->getProvider('form');

        $formConfigs = $formConfigProvider->getConfigs($className);

        // TODO: refactor ConfigIdInterface to allow extracting of field name,
        // TODO: should be done in scope https://magecore.atlassian.net/browse/BAP-1722
        foreach ($formConfigs as $formConfig) {
            $extendConfig = $extendConfigProvider->getConfig($className, $formConfig->getId()->getFieldName());
            if ($formConfig->get('is_enabled')
                && !$extendConfig->is('is_deleted')
                && $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                && !in_array($formConfig->getId()->getFieldType(), array('ref-one', 'ref-many'))
            ) {
                return true;
            }
        }

        return false;
    }
}
