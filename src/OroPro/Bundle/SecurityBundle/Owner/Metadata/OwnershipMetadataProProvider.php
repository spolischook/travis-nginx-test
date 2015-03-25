<?php

namespace OroPro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class OwnershipMetadataProProvider extends OwnershipMetadataProvider
{

    public function __construct(
        array $owningEntityNames,
        ConfigProvider $configProvider,
        EntityClassResolver $entityClassResolver = null,
        CacheProvider $cache = null
    ) {
        parent::__construct($owningEntityNames, $configProvider, $entityClassResolver, $cache);

        $this->noOwnershipMetadata = new OwnershipProMetadata();
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadata(
        $ownerType,
        $ownerFieldName,
        $ownerColumnName,
        $organizationFieldName,
        $organizationColumnName
    ) {
        $data = new OwnershipProMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName
        );
        return $data;
    }
}
