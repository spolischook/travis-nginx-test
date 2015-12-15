<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;

class ExtendConfigLoader extends ConfigLoader
{
    /**
     * {@inheritdoc}
     */
    protected function hasEntityConfigs(ClassMetadata $metadata)
    {
        return parent::hasEntityConfigs($metadata) && !ExtendHelper::isCustomEntity($metadata->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function hasFieldConfigs(ClassMetadata $metadata, $fieldName)
    {
        $className = $metadata->getName();
        if ($this->isExtendField($className, $fieldName)) {
            return false;
        }

        // check for "snapshot" field of multi-enum type
        $snapshotSuffixOffset = -strlen(ExtendHelper::ENUM_SNAPSHOT_SUFFIX);
        if (substr($fieldName, $snapshotSuffixOffset) === ExtendHelper::ENUM_SNAPSHOT_SUFFIX) {
            $guessedName = substr($fieldName, 0, $snapshotSuffixOffset);
            if (!empty($guessedName) && $this->isExtendField($className, $guessedName)) {
                return false;
            }
        }

        return parent::hasFieldConfigs($metadata, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasAssociationConfigs(ClassMetadata $metadata, $associationName)
    {
        $className = $metadata->getName();
        if ($this->isExtendField($className, $associationName)) {
            return false;
        }

        // check for default field of oneToMany or manyToMany relation
        if (strpos($associationName, ExtendConfigDumper::DEFAULT_PREFIX) === 0) {
            $guessedName = substr($associationName, strlen(ExtendConfigDumper::DEFAULT_PREFIX));
            if (!empty($guessedName) && $this->isExtendField($className, $guessedName)) {
                return false;
            }
        }
        // check for inverse side field of oneToMany relation
        $targetClass = $metadata->getAssociationTargetClass($associationName);
        $prefix      = strtolower(ExtendHelper::getShortClassName($targetClass)) . '_';
        if (strpos($associationName, $prefix) === 0) {
            $guessedName = substr($associationName, strlen($prefix));
            if (!empty($guessedName) && $this->isExtendField($targetClass, $guessedName)) {
                return false;
            }
        }

        return parent::hasAssociationConfigs($metadata, $associationName);
    }

    /**
     * Determines whether a field is extend or not
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isExtendField($className, $fieldName)
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            return $this->configManager
                ->getProvider('extend')
                ->getConfig($className, $fieldName)
                ->is('is_extend');
        }

        return false;
    }
}
