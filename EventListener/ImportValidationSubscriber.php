<?php

namespace OroCRMPro\Bundle\LDAPBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;

use OroCRMPro\Bundle\LDAPBundle\ImportExport\UserImportStrategy;

class ImportValidationSubscriber
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ImportStrategyHelper */
    protected $strategyHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var array */
    protected $uniqueFieldsCache = [];

    /** @var array */
    protected $existingUniqueValues = [];

    /** @var ContextInterface */
    protected $lastContext;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ImportStrategyHelper $strategyHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, ImportStrategyHelper $strategyHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->strategyHelper = $strategyHelper;
    }

    /**
     * @param StrategyEvent $event
     */
    public function beforeProcess(StrategyEvent $event)
    {
        if (!$event->getStrategy() instanceof UserImportStrategy) {
            return;
        }
        $this->updateContext($event->getContext());

        $entity = $event->getEntity();
        $violations = $this->validateEntityUniqueFields($entity);
        if ($violations) {
            $event->getContext()->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors(
                [
                    sprintf(
                        'Entity with following unique fields: "%s" was already imported',
                        implode(
                            ', ',
                            array_map(
                                function ($key, $value) {
                                    return sprintf('%s: %s', $key, $value);
                                },
                                array_keys($violations),
                                $violations
                            )
                        )
                    )
                ],
                $event->getContext()
            );
            $event->setEntity(null);
        }
    }

    /**
     * @param ContextInterface $context
     */
    protected function updateContext(ContextInterface $context)
    {
        if ($this->lastContext === $context) {
            return;
        }

        $this->lastContext = $context;
        $this->existingUniqueValues = [];
    }

    /**
     * @param object $entity
     *
     * @return array Violations
     */
    protected function validateEntityUniqueFields($entity)
    {
        $uniqueFields = $this->getUniqueFieldNames($entity);
        if (!$uniqueFields) {
            return [];
        }

        $fieldValues = $this->getFieldValues($entity, $uniqueFields);
        $className = ClassUtils::getClass($entity);
        if (!array_key_exists($className, $this->existingUniqueValues)) {
            $this->existingUniqueValues[$className] = array_fill_keys(array_keys($fieldValues), []);
        }

        $violations = [];
        $existingValues = $this->existingUniqueValues[$className];
        foreach ($fieldValues as $fieldName => $value) {
            if (in_array($value, $existingValues[$fieldName])) {
                $violations[$fieldName] = $value;
            } else {
                $this->existingUniqueValues[$className][$fieldName][] = $value;
            }
        }

        return $violations;
    }

    /**
     * @param object $entity
     * @param array $fields
     *
     * @return array
     */
    protected function getFieldValues($entity, $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!$this->getPropertyAccessor()->isReadable($entity, $field)) {
                continue;
            }

            $result[$field] = $this->getPropertyAccessor()->getValue($entity, $field);
        }

        return $result;
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    protected function getUniqueFieldNames($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        if (!array_key_exists($entityClass, $this->uniqueFieldsCache)) {
            $this->generateUniqueFieldNameCache($entityClass);
        }

        return $this->uniqueFieldsCache[$entityClass];
    }

    /**
     * @param string $entityClass
     */
    protected function generateUniqueFieldNameCache($entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $this->uniqueFieldsCache[$entityClass] = array_map(
            function ($field) {
                return $field['fieldName'];
            },
            array_filter(
                $metadata->fieldMappings,
                function ($field) {
                    return isset($field['unique']) && $field['unique'];
                }
            )
        );
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
