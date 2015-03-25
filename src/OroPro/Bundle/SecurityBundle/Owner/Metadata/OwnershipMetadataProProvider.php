<?php

namespace OroPro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
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
    protected function ensureMetadataLoaded($className)
    {
        if (!isset($this->localCache[$className])) {
            $data = null;
            if ($this->cache) {
                $data = $this->cache->fetch($className);
            }
            if (!$data) {
                if ($this->configProvider->hasConfig($className)) {
                    $config = $this->configProvider->getConfig($className);
                    try {
                        $ownerType              = $config->get('owner_type');
                        $ownerFieldName         = $config->get('owner_field_name');
                        $ownerColumnName        = $config->get('owner_column_name');
                        $organizationFieldName  = $config->get('organization_field_name');
                        $organizationColumnName = $config->get('organization_column_name');
                        $globalView   = $config->get('global_view');

                        if (!$organizationFieldName && $ownerType == OwnershipType::OWNER_TYPE_ORGANIZATION) {
                            $organizationFieldName  = $ownerFieldName;
                            $organizationColumnName = $ownerColumnName;
                        }

                        $data = new OwnershipProMetadata(
                            $ownerType,
                            $ownerFieldName,
                            $ownerColumnName,
                            $organizationFieldName,
                            $organizationColumnName,
                            $globalView
                        );
                    } catch (\InvalidArgumentException $ex) {
                        throw new InvalidConfigurationException(
                            sprintf('Invalid entity ownership configuration for "%s".', $className),
                            0,
                            $ex
                        );
                    }
                }
                if (!$data) {
                    $data = true;
                }

                if ($this->cache) {
                    $this->cache->save($className, $data);
                }
            }

            $this->localCache[$className] = $data;
        }
    }
}
