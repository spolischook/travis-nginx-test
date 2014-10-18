<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsExtension as BaseDynamicFieldsExtension;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    /** @var  SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigManager  $configManager
     * @param Router         $router
     * @param Translator     $translator
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        Translator $translator,
        SecurityFacade $securityFacade
    ) {
        parent::__construct($configManager, $router, $translator);

        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigProviderInterface $extendConfigProvider)
    {
        if (parent::isApplicableField($extendConfig, $extendConfigProvider)) {
            $organizationConfigProvider = $this->configManager->getProvider('organization');
            $organizationConfig = $organizationConfigProvider->getConfig(
                $extendConfig->getId()->getClassName(),
                $extendConfig->getId()->getFieldName()
            );
            $applicable = $organizationConfig->get('applicable', false);

            return
                $applicable
                && (
                    $applicable['all'] === true
                    || in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])
                );
        }

        return false;
    }
}
