<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SearchResultOrganizationExtension extends \Twig_Extension
{
    const NAME = 'oropro_search_organization';

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
            new \Twig_SimpleFunction('oropro_search_entity_organization_info', [$this, 'getOrganizationInfo']),
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
     * If user works in Global mode - return organization name for given search result entity
     *
     * @param object $entity
     * @return string|null
     */
    public function getOrganizationInfo($entity)
    {
        if ($this->securityFacade->getOrganization()->getIsGlobal()) {
            $className = $this->doctrineHelper->getEntityClass($entity);
            if ($this->configProvider->hasConfig($className)) {
                $config  = $this->configProvider->getConfig($className);

                $organizationFieldName = '';
                if (in_array($config->get('owner_type'), ['USER', 'BUSINESS_UNIT'])) {
                    $organizationFieldName = $config->get('organization_field_name');
                } elseif ($config->get('owner_type') === 'ORGANIZATION') {
                    $organizationFieldName = $config->get('owner_field_name');
                }
                if ($organizationFieldName) {
                    return PropertyAccess::createPropertyAccessor()
                        ->getValue($entity, $organizationFieldName)
                        ->getName();
                }
            }
        }

        return null;
    }
}
