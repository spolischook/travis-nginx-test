<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class GlobalOrganizationExtension extends \Twig_Extension
{
    const NAME = 'oropro_global_organization';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param ConfigProvider $configProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigProvider $configProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->securityFacade = $securityFacade;
        $this->configProvider = $configProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oropro_entity_organization_name',
                [$this, 'getOrganizationName']
            ),
            new \Twig_SimpleFunction(
                'oropro_entity_organization',
                [$this, 'getGlobalOrganization']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * Return organization name for given entity
     *
     * @param object $entity
     * @return string|null
     */
    public function getOrganizationName($entity)
    {
        $organization = $this->getEntityOrganization($entity);
        if ($organization) {
            return $organization->getName();
        }

        return null;
    }

    /**
     * @param $entity
     *
     * @return Organization|null
     */
    public function getGlobalOrganization($entity)
    {
        return $this->getEntityOrganization($entity);
    }

    /**
     * If user works in System access mode - return entity organization
     *
     * @param $entity
     * @return Organization|null
     */
    protected function getEntityOrganization($entity)
    {
        if ($this->securityFacade->getOrganization()->getIsGlobal()) {
            $className = $this->doctrineHelper->getEntityClass($entity);
            if ($this->configProvider->hasConfig($className)) {
                $config = $this->configProvider->getConfig($className);
                $organizationFieldName = '';
                if (in_array($config->get('owner_type'), ['USER', 'BUSINESS_UNIT'])) {
                    $organizationFieldName = $config->get('organization_field_name');
                } elseif ($config->get('owner_type') === 'ORGANIZATION') {
                    $organizationFieldName = $config->get('owner_field_name');
                }
                if ($organizationFieldName) {
                    return PropertyAccess::createPropertyAccessor()->getValue($entity, $organizationFieldName);
                }
            }
        }

        return null;
    }
}
