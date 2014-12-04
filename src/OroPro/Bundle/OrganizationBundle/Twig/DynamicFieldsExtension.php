<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

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
    )
    {
        parent::__construct($configManager, $fieldTypeHelper, $dispatcher);

        $this->securityFacade       = $securityFacade;
        $this->organizationProvider = $configManager->getProvider('organization');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields($entity, $entityClass = null)
    {
        if (method_exists($entity, 'getOrganization')) {
            $this->entityOrganization = $entity->getOrganization();
        } else {
            $this->entityOrganization = $this->securityFacade->getOrganization();
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
            $organizationId     = $this->securityFacade->getOrganization()->getIsGlobal()
                ? $this->entityOrganization->getId()
                : $this->securityFacade->getOrganizationId();

            // skip field if it's not configured for current organization
            $applicable = $organizationConfig->get('applicable', false, false);

            return
                $applicable
                && ($applicable['all'] || in_array($organizationId, $applicable['selective']));
        }

        return false;
    }
}
