<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\ORM\EntityManager;

use Oro\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

class WorkflowEntityValidator extends ConstraintValidator
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var WorkflowPermissionRegistry */
    protected $permissionRegistry;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param EntityManager              $entityManager
     * @param DoctrineHelper             $doctrineHelper
     * @param ConfigProvider             $configProvider
     * @param WorkflowPermissionRegistry $permissionRegistry
     * @param RestrictionManager         $restrictionManager
     */
    public function __construct(
        EntityManager $entityManager,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        WorkflowPermissionRegistry $permissionRegistry,
        RestrictionManager $restrictionManager
    ) {
        $this->entityManager      = $entityManager;
        $this->doctrineHelper     = $doctrineHelper;
        $this->configProvider     = $configProvider;
        $this->permissionRegistry = $permissionRegistry;
        $this->restrictionManager = $restrictionManager;

        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * {@inheritdoc}
     * @param WorkflowEntity $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_object($value)) {
            return;
        }

        // Skip changes for workflow transition form
        $root = $this->context->getRoot();
        if ($root instanceof Form) {
            if (WorkflowTransitionType::NAME === $root->getName()) {
                return;
            }
        }

        if (!$this->configProvider->hasConfig($value)) {
            return;
        }

        $config = $this->configProvider->getConfig($value);
        if (!$config->get('active_workflow', false, false)) {
            return;
        }

        $restrictions = $this->restrictionManager->getEntitiesRestrictions($value);
        if ($this->doctrineHelper->isNewEntity($value)) {
            $this->validateNewEntity($value, $constraint, $restrictions);
        } else {
            $permissions = $this->permissionRegistry->getEntityPermissions($value);
            if ($permissions['UPDATE'] === false || $restrictions) {
                $unitOfWork = $this->entityManager->getUnitOfWork();
                $class      = $this->entityManager->getClassMetadata($this->configProvider->getClassName($value));
                $unitOfWork->computeChangeSet($class, $value);
                if ($permissions['UPDATE'] === false) {
                    if ($unitOfWork->isScheduledForUpdate($value)) {
                        $this->context->addViolation($constraint->updateEntityMessage);
                    }
                } else {
                    $this->validateUpdatedFields($value, $constraint, $restrictions);
                }
            }
        }
    }

    /**
     * @param object         $object
     * @param WorkflowEntity $constraint
     * @param array          $restrictions
     */
    protected function validateNewEntity($object, WorkflowEntity $constraint, array $restrictions)
    {
        foreach ($restrictions as $restriction) {
            $fieldValue = $this->propertyAccessor->getValue($object, $restriction['field']);
            if ($restriction['mode'] === 'full') {
                if ($fieldValue !== null) {
                    $this->addFieldViolation($restriction['field'], $constraint->createFieldMessage);
                }
            } elseif ($restriction['mode'] === 'disallow') {
                $this->validateAllowedValues($object, $constraint, $restriction['field'], $restriction['values']);
            }
        }
    }

    /**
     * @param object         $object
     * @param WorkflowEntity $constraint
     * @param array          $restrictions
     */
    protected function validateUpdatedFields($object, WorkflowEntity $constraint, array $restrictions)
    {
        $unitOfWork       = $this->entityManager->getUnitOfWork();
        $changesSet       = $unitOfWork->getEntityChangeSet($object);
        $restrictedFields = array_flip(
            array_map(
                function ($restriction) {
                    return $restriction['field'];
                },
                $restrictions
            )
        );

        if ($fields = array_intersect_key($changesSet, $restrictedFields)) {
            foreach ($restrictions as $restriction) {
                 foreach ($fields as $key => $value) {
                    if ($restriction['field'] === $key) {
                        if ($restriction['mode'] === 'full') {
                            $this->addFieldViolation($key, $constraint->updateFieldMessage);
                        } elseif ($restriction['mode'] === 'disallow') {
                            $this->validateAllowedValues($object, $constraint, $key, $restriction['values']);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param object         $object
     * @param WorkflowEntity $constraint
     * @param string         $field
     * @param array          $allowedValues
     */
    protected function validateAllowedValues($object, WorkflowEntity $constraint, $field, array $allowedValues)
    {
        $fieldValue = $this->propertyAccessor->getValue($object, $field);
        if (is_object($fieldValue)) {
            $fieldValue = $this->doctrineHelper->getSingleEntityIdentifier($fieldValue);
        }

        if (in_array($fieldValue, $allowedValues, true)) {
            $this->addFieldViolation($field, $constraint->updateFieldMessage);
        }
    }

    /**
     * @param string $field
     * @param string $message
     */
    protected function addFieldViolation($field, $message)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context
            ->buildViolation($message)
            ->atPath($field)
            ->addViolation();
    }
}
