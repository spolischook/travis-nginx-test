<?php

namespace OroPro\Bundle\OrganizationBundle\Api\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use OroPro\Bundle\OrganizationBundle\Validator\Constraints\Organization;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

/**
 * For "create" action adds NotNull validation constraint for "organization" field.
 * Adds NotBlank validation constraint for "organization" field.
 * Adds Organization validation constraint for the entity.
 */
class AddOrganizationValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProProvider */
    protected $ownershipMetadataProvider;

    /** @var ValidationHelper */
    protected $validationHelper;

    /**
     * @param DoctrineHelper               $doctrineHelper
     * @param OwnershipMetadataProProvider $ownershipMetadataProvider
     * @param ValidationHelper             $validationHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProProvider $ownershipMetadataProvider,
        ValidationHelper $validationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->validationHelper = $validationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->addValidators($context->getResult(), $entityClass, $context->getTargetAction());
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     */
    protected function addValidators(EntityDefinitionConfig $definition, $entityClass, $targetAction)
    {
        $fieldName = $this->ownershipMetadataProvider->getMetadata($entityClass)->getGlobalOwnerFieldName();
        if (!$fieldName) {
            return;
        }
        $field = $definition->findField($fieldName, true);
        if (null === $field) {
            return;
        }

        // add NotNull validation
        if (ApiActions::CREATE === $targetAction) {
            $field->addFormConstraint(new NotNull());
        }

        // add NotBlank constraint
        $property = $field->getPropertyPath() ?: $fieldName;
        if (!$this->validationHelper->hasValidationConstraintForProperty($entityClass, $property, NotBlank::class)) {
            $field->addFormConstraint(new NotBlank());
        }

        // add organization validator
        if (!$this->validationHelper->hasValidationConstraintForClass($entityClass, Organization::class)) {
            $definition->addFormConstraint(new Organization());
        }
    }
}
