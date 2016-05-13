<?php

namespace OroPro\Bundle\OrganizationBundle\Api\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use OroPro\Bundle\OrganizationBundle\Validator\Constraints\Organization;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

/**
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

        $definition = $context->getResult();
        $fieldName = $this->ownershipMetadataProvider->getMetadata($entityClass)->getGlobalOwnerFieldName();
        if (!$fieldName) {
            return;
        }
        $field = $definition->findField($fieldName, true);
        if (null === $field) {
            return;
        }

        // add NotBlank constraint
        if (!$this->validationHelper->hasValidationConstraintForProperty(
            $entityClass,
            $field->getPropertyPath() ?: $fieldName,
            'Symfony\Component\Validator\Constraints\NotBlank'
        )) {
            $fieldOptions = $field->getFormOptions();
            $fieldOptions['constraints'][] = new NotBlank();
            $field->setFormOptions($fieldOptions);
        }

        // add organization validator
        if (!$this->validationHelper->hasValidationConstraintForClass(
            $entityClass,
            'OroPro\Bundle\OrganizationBundle\Validator\Constraints\Organization'
        )) {
            $entityOptions = $definition->getFormOptions();
            $entityOptions['constraints'][] = new Organization();
            $definition->setFormOptions($entityOptions);
        }
    }
}
