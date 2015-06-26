<?php

namespace OroPro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class OwnershipMetadataProProvider extends OwnershipMetadataProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getNoOwnershipMetadata()
    {
        return new OwnershipProMetadata();
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        $ownerType              = $config->get('owner_type');
        $ownerFieldName         = $config->get('owner_field_name');
        $ownerColumnName        = $config->get('owner_column_name');
        $organizationFieldName  = $config->get('organization_field_name');
        $organizationColumnName = $config->get('organization_column_name');
        $globalView             = $config->get('global_view');

        if (!$organizationFieldName && $ownerType == OwnershipType::OWNER_TYPE_ORGANIZATION) {
            $organizationFieldName  = $ownerFieldName;
            $organizationColumnName = $ownerColumnName;
        }

        return new OwnershipProMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName,
            $globalView
        );
    }
}
