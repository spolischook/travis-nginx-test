<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class FieldSecurityMetadataProvider
{
    const ACL_SECURITY_TYPE = 'ACL';

    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /** @var ConfigProvider */
    protected $securityConfigProvider;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var array
     *     key = security type
     *     value = array
     *         key = class name
     *         value = EntitySecurityMetadata
     */
    protected $localCache = [];

    /**
     * @param ConfigProvider     $securityConfigProvider
     * @param ConfigProvider     $entityConfigProvider
     * @param CacheProvider|null $cache
     * @param ConfigProvider     $extendConfigProvider
     */
    public function __construct(
        ConfigProvider $securityConfigProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        ManagerRegistry $doctrine,
        CacheProvider $cache = null
    ) {
        $this->securityConfigProvider = $securityConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->doctrine = $doctrine;
        $this->cache = $cache;
    }

    /**
     * Checks whether an entity is protected using the given security type.
     *
     * @param string $className    The entity class name
     * @param string $securityType The security type. Defaults to ACL.
     *
     * @return bool
     */
    public function supports($className, $securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureMetadataLoaded($securityType);

        return isset($this->localCache[$securityType][$className]);
    }

    /**
     * Gets metadata for all entities marked with the given security type.
     *
     * @param string $securityType The security type. Defaults to ACL.
     *
     * @return EntityFieldSecurityMetadata[]
     */
    public function getEntities($securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureMetadataLoaded($securityType);

        return array_values($this->localCache[$securityType]);
    }

    /**
     * @param string $className
     * @param string $securityType
     *
     * @return EntityFieldSecurityMetadata|null
     */
    public function getClassFields($className, $securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureMetadataLoaded($securityType);
        return isset($securityType, $this->localCache) && isset($className, $this->localCache[$securityType])
            ? $this->localCache[$securityType][$className]
            : null;
    }

    /**
     * Warms up the cache
     */
    public function warmUpCache()
    {
        $securityTypes = [];
        foreach ($this->securityConfigProvider->getConfigs() as $securityConfig) {
            $securityType = $securityConfig->get('type');
            if ($securityType && !in_array($securityType, $securityTypes, true)) {
                $securityTypes[] = $securityType;
            }
        }
        foreach ($securityTypes as $securityType) {
            $this->loadMetadata($securityType);
        }
    }

    /**
     * Clears the cache by security type
     *
     * If the $securityType is not specified, clear all cached data
     *
     * @param string|null $securityType The security type.
     */
    public function clearCache($securityType = null)
    {
        if ($this->cache) {
            if ($securityType !== null) {
                $this->cache->delete($securityType);
            } else {
                $this->cache->deleteAll();
            }
        }
        if ($securityType !== null) {
            unset($this->localCache[$securityType]);
        } else {
            $this->localCache = [];
        }
    }

    /**
     * Get field metadata
     *
     * @param string $className
     * @param string $securityType
     *
     * @return EntitySecurityMetadata
     */
    public function getMetadata($className, $securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureMetadataLoaded($securityType);

        $result = $this->localCache[$securityType][$className];
        if ($result === true) {
            return new EntitySecurityMetadata();
        }

        return $result;
    }

    /**
     * Makes sure that metadata for the given security type are loaded and cached
     *
     * @param string $securityType The security type.
     */
    protected function ensureMetadataLoaded($securityType)
    {
        if (!isset($this->localCache[$securityType])) {
            $data = null;
            if ($this->cache) {
                $data = $this->cache->fetch($securityType);
            }
            if ($data) {
                $this->localCache[$securityType] = $data;
            } else {
                $this->loadMetadata($securityType);
            }
        }
    }

    /**
     * Loads metadata for the given security type and save them in cache
     *
     * @param $securityType
     */
    protected function loadMetadata($securityType)
    {
        $data = [];
        $securityConfigs = $this->securityConfigProvider->getConfigs();
        foreach ($securityConfigs as $securityConfig) {
            $className = $securityConfig->getId()->getClassName();

            if ($securityConfig->get('type') === $securityType
                && $this->extendConfigProvider->getConfig($className)->in(
                    'state',
                    [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
                )
                && $securityConfig->get('field_acl_supported')
                && $securityConfig->get('field_acl_enabled')
            ) {
                $fields = [];
                $fieldsConfig = $securityConfigs = $this->securityConfigProvider->getConfigs($className);
                $groupName = $securityConfig->get('group_name');
                $classMetadata = $this->doctrine
                    ->getManagerForClass($className)
                    ->getMetadataFactory()
                    ->getMetadataFor($className);

                foreach ($fieldsConfig as $fieldInfo) {
                    $fieldName = $fieldInfo->getId()->getFieldName();
                    if ($classMetadata->isIdentifier($fieldName)) {
                        // we should not limit access to identifier fields.
                        continue;
                    }
                    $fields[$fieldName] = new FieldSecurityMetadata(
                        $fieldName,
                        $this->getFieldLabel($classMetadata, $fieldName)
                    );
                }
                $label = '';
                if ($this->entityConfigProvider->hasConfig($className)) {
                    $label = $this->entityConfigProvider
                        ->getConfig($className)
                        ->get('label');
                }
                $data[$className] = new EntityFieldSecurityMetadata(
                    $securityType,
                    $className,
                    $groupName,
                    $label,
                    $fields
                );

            }
        }

        if ($this->cache) {
            $this->cache->save($securityType, $data);
        }

        $this->localCache[$securityType] = $data;
    }

    /**
     * Gets a label of a field
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return string
     */
    protected function getFieldLabel(ClassMetadata $metadata, $fieldName)
    {
        $className = $metadata->getName();
        if (!$metadata->hasField($fieldName) && !$metadata->hasAssociation($fieldName)) {
            // virtual field or relation
            return ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
        }

        $label = $this->entityConfigProvider->hasConfig($className, $fieldName)
            ? $this->entityConfigProvider->getConfig($className, $fieldName)->get('label')
            : null;

        return !empty($label)
            ? $label
            : ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
    }
}
