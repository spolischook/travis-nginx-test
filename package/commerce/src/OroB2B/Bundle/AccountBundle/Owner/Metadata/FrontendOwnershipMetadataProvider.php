<?php

namespace OroB2B\Bundle\AccountBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendOwnershipMetadataProvider extends AbstractMetadataProvider
{
    const ALIAS = 'frontend_ownership';

    /**
     * @var string
     */
    protected $localLevelClass;

    /**
     * @var string
     */
    protected $basicLevelClass;

    /**
     * @var ConfigProvider
     */
    private $securityConfigProvider;

    /**
     * @var FrontendOwnershipMetadataProvider
     */
    private $noOwnershipMetadata;

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * {@inheritDoc}
     */
    protected function setAccessLevelClasses(array $owningEntityNames, EntityClassResolver $entityClassResolver = null)
    {
        if (!isset($owningEntityNames['local_level'], $owningEntityNames['basic_level'])) {
            throw new \InvalidArgumentException(
                'Array parameter $owningEntityNames must contains `local_level` and `basic_level` keys'
            );
        }

        $this->localLevelClass = $this->getEntityClassResolver()->getEntityClass($owningEntityNames['local_level']);
        $this->basicLevelClass = $this->getEntityClassResolver()->getEntityClass($owningEntityNames['basic_level']);
    }

    /**
     * @return ConfigProvider
     */
    protected function getSecurityConfigProvider()
    {
        if (!$this->securityConfigProvider) {
            $this->securityConfigProvider = $this->getContainer()->get('oro_entity_config.provider.security');
        }

        return $this->securityConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNoOwnershipMetadata()
    {
        if (!$this->noOwnershipMetadata) {
            $this->noOwnershipMetadata = new FrontendOwnershipMetadata();
        }

        return $this->noOwnershipMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemLevelClass()
    {
        throw new \BadMethodCallException('Method getSystemLevelClass() unsupported.');
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalLevelClass()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalLevelClass($deep = false)
    {
        return $this->localLevelClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicLevelClass()
    {
        return $this->basicLevelClass;
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof AccountUser;
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        $ownerType              = $config->get('frontend_owner_type');
        $ownerFieldName         = $config->get('frontend_owner_field_name');
        $ownerColumnName        = $config->get('frontend_owner_column_name');
        $organizationFieldName  = $config->get('organization_field_name');
        $organizationColumnName = $config->get('organization_column_name');

        return new FrontendOwnershipMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxAccessLevel($accessLevel, $className = null)
    {
        if ($className) {
            if (in_array($accessLevel, [AccessLevel::NONE_LEVEL, AccessLevel::BASIC_LEVEL, AccessLevel::LOCAL_LEVEL])) {
                $maxLevel = $accessLevel;
            } else {
                $metadata = $this->getMetadata($className);
                if ($metadata->hasOwner()) {
                    $maxLevel = AccessLevel::LOCAL_LEVEL;
                } else {
                    $maxLevel = $accessLevel;
                }
            }
        } else {
            $maxLevel = $accessLevel;
        }

        return $maxLevel;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getContainer()
                ->get('orob2b_account.owner.frontend_ownership_metadata_provider.cache');
        }

        return $this->cache;
    }

    /**
     * Only commerce entities can have frontend ownership
     *
     * {@inheritdoc}
     */
    protected function getOwnershipConfigs()
    {
        $securityProvider = $this->getSecurityConfigProvider();

        $configs = parent::getOwnershipConfigs();

        foreach ($configs as $key => $value) {
            $className = $value->getId()->getClassName();
            if ($securityProvider->hasConfig($className)) {
                $securityConfig = $securityProvider->getConfig($className);
                if ($securityConfig->get('group_name') === AccountUser::SECURITY_GROUP) {
                    continue;
                }
            }

            unset($configs[$key]);
        }

        return $configs;
    }
}
