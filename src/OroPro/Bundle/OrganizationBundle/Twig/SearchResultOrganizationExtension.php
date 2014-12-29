<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SearchResultOrganizationExtension extends \Twig_Extension
{
    const NAME = 'oropro_search_organization';

    const ORGANIZATION_INFO_TEMPLATE = 'OroProOrganizationBundle::organizationInfo.html.twig';

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
                'oropro_entity_organization_info',
                [$this, 'getOrganizationInfo'],
                ['is_safe' => ['html'], 'needs_environment' => true]
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
     * Return entity organization name with hidden organization id field
     *
     * @param \Twig_Environment $environment
     * @param object            $entity
     * @return string
     */
    public function getOrganizationInfo(\Twig_Environment $environment, $entity)
    {
        $organization = $this->getEntityOrganization($entity);
        if ($organization) {
            return $environment->loadTemplate(self::ORGANIZATION_INFO_TEMPLATE)->render(
                [
                    'organization' => $organization
                ]
            );
        }

        return '';
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
