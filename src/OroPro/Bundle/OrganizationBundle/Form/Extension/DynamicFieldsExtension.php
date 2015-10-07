<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsExtension as BaseDynamicFieldsExtension;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var SystemAccessModeOrganizationProvider */
    protected $systemAccessModeOrganizationProvider;

    /**
     * @param ConfigManager                        $configManager
     * @param Router                               $router
     * @param TranslatorInterface                  $translator
     * @param DoctrineHelper                       $doctrineHelper
     * @param SecurityFacade                       $securityFacade
     * @param SystemAccessModeOrganizationProvider $systemAccessModeOrganizationProvider
     */
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        SystemAccessModeOrganizationProvider $systemAccessModeOrganizationProvider
    ) {
        parent::__construct($configManager, $router, $translator, $doctrineHelper);

        $this->securityFacade                       = $securityFacade;
        $this->systemAccessModeOrganizationProvider = $systemAccessModeOrganizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigProvider $extendConfigProvider)
    {
        if (parent::isApplicableField($extendConfig, $extendConfigProvider)) {
            $organizationConfigProvider = $this->configManager->getProvider('organization');
            $organizationConfig         = $organizationConfigProvider->getConfig(
                $extendConfig->getId()->getClassName(),
                $extendConfig->getId()->getFieldName()
            );

            $applicable = $organizationConfig->get('applicable', false);
            $organizationId = $this->systemAccessModeOrganizationProvider->getOrganizationId() ? :
                $this->securityFacade->getOrganizationId();

            return
                $applicable
                && (
                    $applicable['all'] === true
                    || in_array($organizationId, $applicable['selective'])
                );
        }

        return false;
    }
}
