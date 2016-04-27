<?php

namespace OroPro\Bundle\OrganizationBundle\Api\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

use OroPro\Bundle\OrganizationBundle\Validator\Constraints\Organization;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

/**
 * Adds organization validators to entity.
 */
class AddOrganizationValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param DoctrineHelper               $doctrineHelper
     * @param OwnershipMetadataProProvider $ownershipMetadataProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, OwnershipMetadataProProvider $ownershipMetadataProvider)
    {
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
        $fields = $definition->getFields();
        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
        $ownerField = $ownershipMetadata->getGlobalOwnerFieldName();
        if (array_key_exists($ownerField, $fields)) {
            $field = $fields[$ownerField];
            $fieldOptions = $field->getFormOptions();
            $fieldOptions['constraints'][] = new NotBlank();
            $field->setFormOptions($fieldOptions);

            // add organization validator
            $formOptions = $definition->getFormOptions();
            $formOptions['constraints'][] = new Organization();
            $definition->setFormOptions($formOptions);
        }
    }
}
