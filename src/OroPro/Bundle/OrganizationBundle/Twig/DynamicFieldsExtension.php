<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension as BaseDynamicFieldsExtension;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigProvider */
    protected $organizationProvider;

    /** @var Organization|null */
    protected $entityOrganization = null;

    /**
     * @param ConfigManager            $configManager
     * @param FieldTypeHelper          $fieldTypeHelper
     * @param EventDispatcherInterface $dispatcher
     * @param SecurityFacade           $securityFacade
     */
    public function __construct(
        ConfigManager $configManager,
        FieldTypeHelper $fieldTypeHelper,
        EventDispatcherInterface $dispatcher,
        SecurityFacade $securityFacade
    ) {
        parent::__construct($configManager, $fieldTypeHelper, $dispatcher);

        $this->securityFacade       = $securityFacade;
        $this->organizationProvider = $configManager->getProvider('organization');
        $this->ownershipProvider    = $configManager->getProvider('ownership');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields($entity, $entityClass = null)
    {
        $organizationFieldName = null;
        if (null === $entityClass) {
            $entityClass = ClassUtils::getRealClass($entity);
        }
        if ($this->ownershipProvider->hasConfig($entityClass)) {
            $ownershipConfig = $this->ownershipProvider->getConfig($entityClass);
            switch ($ownershipConfig->get('owner_type')) {
                case 'USER':
                case 'BUSINESS_UNIT':
                    $organizationFieldName = $ownershipConfig->get('organization_field_name');
                    break;
                case 'ORGANIZATION':
                    $organizationFieldName = $ownershipConfig->get('owner_field_name');
                    break;
            }
        }
        if ($organizationFieldName) {
            $this->entityOrganization = $this->propertyAccessor->getValue($entity, $organizationFieldName);
        }

        return parent::getFields($entity, $entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function filterFields(ConfigInterface $config)
    {
        if (parent::filterFields($config)) {
            $organizationConfig = $this->organizationProvider->getConfigById($config->getId());

            // skip field if it's not configured for current organization
            $applicable = $organizationConfig->get('applicable', false, false);
            if ($applicable) {
                if ($applicable['all']) {
                    return true;
                } else {
                    if ($this->securityFacade->getOrganization()->getIsGlobal() && $this->entityOrganization !== null) {
                        $organizationId = $this->entityOrganization->getId();
                    } else {
                        $organizationId = $this->securityFacade->getOrganizationId();
                    }

                    return in_array($organizationId, $applicable['selective']);
                }
            }
        }

        return false;
    }
}
