<?php

namespace OroPro\Bundle\OrganizationBundle\Api\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

/**
 * Adds NotNull validation constraint for "organization" field.
 */
class AddOrganizationNotNullValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param DoctrineHelper               $doctrineHelper
     * @param OwnershipMetadataProProvider $ownershipMetadataProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProProvider $ownershipMetadataProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
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

        $fieldOptions = $field->getFormOptions();
        $fieldOptions['constraints'][] = new NotNull();
        $field->setFormOptions($fieldOptions);
    }
}
