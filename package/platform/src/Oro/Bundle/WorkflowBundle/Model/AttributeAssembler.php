<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\Attribute as BaseAttribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;

class AttributeAssembler extends BaseAbstractAssembler
{
    /**
     * @var AttributeGuesser
     */
    protected $attributeGuesser;

    /**
     * @param AttributeGuesser $attributeGuesser
     */
    public function __construct(AttributeGuesser $attributeGuesser)
    {
        $this->attributeGuesser = $attributeGuesser;
    }

    /**
     * @param WorkflowDefinition $definition,
     * @param array $configuration
     * @return ArrayCollection
     * @throws AssemblerException If configuration is invalid
     */
    public function assemble(WorkflowDefinition $definition, array $configuration)
    {
        $entityAttributeName = $definition->getEntityAttributeName();
        if (!array_key_exists($entityAttributeName, $configuration)) {
            $configuration[$entityAttributeName] = array(
                'label' => $entityAttributeName,
                'type' => 'entity',
                'options' => array(
                    'class' => $definition->getRelatedEntity(),
                ),
            );
        }

        $attributes = new ArrayCollection();
        foreach ($configuration as $name => $options) {
            $attribute = $this->assembleAttribute($definition, $name, $options);
            $attributes->set($name, $attribute);
        }

        return $attributes;
    }

    /**
     * @param WorkflowDefinition $definition
     * @param string $name
     * @param array $options
     * @return BaseAttribute
     */
    protected function assembleAttribute(WorkflowDefinition $definition, $name, array $options)
    {
        if (!empty($options['property_path'])) {
            $options = $this->guessOptions($options, $definition->getRelatedEntity(), $options['property_path']);
        }

        $this->assertOptions($options, array('label', 'type'));
        $this->assertAttributeEntityAcl($options);

        $attribute = new BaseAttribute();
        $attribute->setName($name);
        $attribute->setLabel($options['label']);
        $attribute->setType($options['type']);
        $attribute->setEntityAcl($this->getOption($options, 'entity_acl', array()));
        $attribute->setPropertyPath($this->getOption($options, 'property_path'));
        $attribute->setOptions($this->getOption($options, 'options', array()));

        $this->validateAttribute($attribute);

        return $attribute;
    }

    /**
     * @param array $options
     * @param string $rootClass
     * @param string $propertyPath
     * @return array
     */
    protected function guessOptions(array $options, $rootClass, $propertyPath)
    {
        $guessedOptions = array('label', 'type', 'options');
        $needGuess = false;
        foreach ($guessedOptions as $option) {
            if (empty($options[$option])) {
                $needGuess = true;
                break;
            }
        }

        if (!$needGuess) {
            return $options;
        }

        $attributeParameters = $this->attributeGuesser->guessAttributeParameters($rootClass, $propertyPath);
        if ($attributeParameters) {
            foreach ($guessedOptions as $option) {
                if (empty($options[$option]) && !empty($attributeParameters[$option])) {
                    $options[$option] = $attributeParameters[$option];
                }
            }
        }

        return $options;
    }

    /**
     * @param array $options
     * @throws AssemblerException
     */
    protected function assertAttributeEntityAcl(array $options)
    {
        if ($options['type'] !== 'entity' && array_key_exists('entity_acl', $options)) {
            throw new AssemblerException(
                sprintf(
                    'Attribute "%s" with type "%s" can\'t have entity ACL',
                    $options['label'],
                    $options['type']
                )
            );
        }
    }

    /**
     * @param BaseAttribute $attribute
     * @throws AssemblerException If attribute is invalid
     */
    protected function validateAttribute(BaseAttribute $attribute)
    {
        $this->assertAttributeHasValidType($attribute);

        if ($attribute->getType() == 'object' || $attribute->getType() == 'entity') {
            $this->assertAttributeHasClassOption($attribute);
        } else {
            $this->assertAttributeHasNoOptions($attribute, 'class');
        }
    }

    /**
     * @param BaseAttribute $attribute
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasValidType(BaseAttribute $attribute)
    {
        $attributeType = $attribute->getType();
        $allowedTypes = array('bool', 'boolean', 'int', 'integer', 'float', 'string', 'array', 'object', 'entity');

        if (!in_array($attributeType, $allowedTypes)) {
            throw new AssemblerException(
                sprintf(
                    'Invalid attribute type "%s", allowed types are "%s"',
                    $attributeType,
                    implode('", "', $allowedTypes)
                )
            );
        }
    }

    /**
     * @param BaseAttribute $attribute
     * @param string|array $optionNames
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasOptions(BaseAttribute $attribute, $optionNames)
    {
        $optionNames = (array)$optionNames;

        foreach ($optionNames as $optionName) {
            if (!$attribute->hasOption($optionName)) {
                throw new AssemblerException(
                    sprintf('Option "%s" is required in attribute "%s"', $optionName, $attribute->getName())
                );
            }
        }
    }

    /**
     * @param BaseAttribute $attribute
     * @param string|array $optionNames
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasNoOptions(BaseAttribute $attribute, $optionNames)
    {
        $optionNames = (array)$optionNames;

        foreach ($optionNames as $optionName) {
            if ($attribute->hasOption($optionName)) {
                throw new AssemblerException(
                    sprintf('Option "%s" cannot be used in attribute "%s"', $optionName, $attribute->getName())
                );
            }
        }
    }

    /**
     * @param BaseAttribute $attribute
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasClassOption(BaseAttribute $attribute)
    {
        $this->assertAttributeHasOptions($attribute, 'class');
        if (!class_exists($attribute->getOption('class'))) {
            throw new AssemblerException(
                sprintf(
                    'Class "%s" referenced by "class" option in attribute "%s" not found',
                    $attribute->getOption('class'),
                    $attribute->getName()
                )
            );
        }
    }
}
