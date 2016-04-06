<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds owner validators to entity.
 */
class AddOwnerValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, OwnershipMetadataProvider $ownershipMetadataProvider)
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
        $ownerField = $ownershipMetadata->getOwnerFieldName();
        if (array_key_exists($ownerField, $fields)) {
            $field = $fields[$ownerField];
            // turns on owner field.
            // todo: should be deleted after BAP-10008
            $field->setExcluded(false);

            $fieldOptions = $field->getFormOptions();
            $fieldOptions['constraints'][] = new NotBlank();
            $field->setFormOptions($fieldOptions);

            // add owner validator
            $formOptions = $definition->getFormOptions();
            $formOptions['constraints'][] = new Owner();
            $definition->setFormOptions($formOptions);
        }
    }
}
