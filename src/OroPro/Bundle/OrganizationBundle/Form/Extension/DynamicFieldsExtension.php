<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
     * @param Translator                           $translator
     * @param SecurityFacade                       $securityFacade
     * @param SystemAccessModeOrganizationProvider $systemAccessModeOrganizationProvider
     */
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        Translator $translator,
        SecurityFacade $securityFacade,
        SystemAccessModeOrganizationProvider $systemAccessModeOrganizationProvider
    ) {
        parent::__construct($configManager, $router, $translator);

        $this->securityFacade                       = $securityFacade;
        $this->systemAccessModeOrganizationProvider = $systemAccessModeOrganizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigProviderInterface $extendConfigProvider)
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
