<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Tries to rename fields if they are equal to reserved words.
 * * 'type' field is renamed to 'short class name' + 'Type'
 * * 'id' field is renamed to 'short class name' + 'Id' in case if it is not an identifier of an entity
 */
class FixFieldNaming implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (null === $definition
            || !isset($definition[ConfigUtil::FIELDS])
            || !is_array($definition[ConfigUtil::FIELDS])
        ) {
            // a configuration of fields does not exist or a fix is not needed
            return;
        }

        $entityClass        = $context->getClassName();
        $reservedFieldNames = array_intersect(array_keys($definition[ConfigUtil::FIELDS]), ['id', 'type']);
        foreach ($reservedFieldNames as $fieldName) {
            if ('type' === $fieldName
                || ('id' === $fieldName && !$this->isIdentifierField($entityClass, $fieldName))
            ) {
                $this->renameReservedField($definition[ConfigUtil::FIELDS], $entityClass, $fieldName);
            }
        }
        $context->setResult($definition);
    }

    /**
     * @param array       $fields
     * @param string|null $entityClass
     * @param string      $fieldName
     *
     * @throws \RuntimeException if a field cannot be renamed
     */
    protected function renameReservedField(array &$fields, $entityClass, $fieldName)
    {
        if (!$entityClass) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" reserved word cannot be used as a field name.',
                    $fieldName
                )
            );
        }

        $newFieldName = lcfirst($this->getShortClassName($entityClass)) . ucfirst($fieldName);
        if (array_key_exists($newFieldName, $fields)) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" reserved word cannot be used as a field name'
                    . ' and it cannot be renamed to "%s" because a field with this name already exists.',
                    $fieldName,
                    $newFieldName
                )
            );
        }

        // do renaming
        $fieldConfig = $fields[$fieldName];
        if (null === $fieldConfig) {
            $fieldConfig = [];
        }
        if (empty($fieldConfig[ConfigUtil::DEFINITION][ConfigUtil::PROPERTY_PATH])) {
            $fieldConfig[ConfigUtil::DEFINITION][ConfigUtil::PROPERTY_PATH] =
                array_key_exists(ConfigUtil::PROPERTY_PATH, $fieldConfig)
                    ? $fieldConfig[ConfigUtil::PROPERTY_PATH]
                    : $fieldName;
        }
        unset($fields[$fieldName]);
        $fields[$newFieldName] = $fieldConfig;
    }

    /**
     * Checks whether the given field is an identifier of the given entity
     *
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isIdentifierField($entityClass, $fieldName)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass, false);
        if (null === $metadata) {
            return false;
        }

        $idFieldNames = $metadata->getIdentifierFieldNames();

        return count($idFieldNames) === 1 && reset($idFieldNames) === $fieldName;
    }

    /**
     * Gets the short name of the class, the part without the namespace
     *
     * @param string $className The full name of a class
     *
     * @return string
     */
    protected function getShortClassName($className)
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
