<?php

namespace OroCRM\Bundle\MagentoBundle\ImportExport\Strategy;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class DefaultMagentoImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    protected function updateContextCounters($entity)
    {
        // increment context counter
        $identifier = $this->databaseHelper->getIdentifier($entity);
        if ($identifier) {
            $this->context->incrementUpdateCount();
        } else {
            $this->context->incrementAddCount();
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, 'OroCRM\Bundle\MagentoBundle\Entity\IntegrationAwareInterface', true)) {
            $identityValues['channel'] = $this->context->getOption('channel');
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return $this->propertyAccessor;
    }
}
