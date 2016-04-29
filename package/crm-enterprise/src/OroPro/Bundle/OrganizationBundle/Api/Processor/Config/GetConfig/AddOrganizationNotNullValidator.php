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
    protected $ownershipMetadataProProvider;

    /**
     * @param DoctrineHelper               $doctrineHelper
     * @param OwnershipMetadataProProvider $ownershipMetadataProProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProProvider $ownershipMetadataProProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProProvider = $ownershipMetadataProProvider;
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
        $fields = $definition->getFields();
        $ownerField = $this->ownershipMetadataProProvider->getMetadata($entityClass)->getGlobalOwnerFieldName();
        if (array_key_exists($ownerField, $fields)) {
            $field = $fields[$ownerField];
            $fieldOptions = $field->getFormOptions();
            $fieldOptions['constraints'][] = new NotNull();
            $field->setFormOptions($fieldOptions);
        }
    }
}
