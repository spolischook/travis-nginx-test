<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityBundle\Form\Type\CustomEntityType as BaseCustomEntityType;

class CustomEntityType extends BaseCustomEntityType
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
        $this->configManager = $configManager;
        $this->router        = $router;
        $this->translator    = $translator;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string          $className
     * @param ConfigInterface $formConfig
     *
     * @return bool
     */
    protected function checkAvailability($className, ConfigInterface $formConfig)
    {
        if (parent::checkAvailability($className, $formConfig)) {
            $organizationConfigProvider = $this->configManager->getProvider('organization');
            $organizationConfig = $organizationConfigProvider->getConfig(
                $className,
                $formConfig->getId()->getFieldName()
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
