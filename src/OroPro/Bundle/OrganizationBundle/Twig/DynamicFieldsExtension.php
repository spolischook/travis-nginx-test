<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension as BaseDynamicFieldsExtension;

class DynamicFieldsExtension extends BaseDynamicFieldsExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigManager         $configManager
     * @param FieldTypeHelper       $fieldTypeHelper
     * @param DateTimeFormatter     $dateTimeFormatter
     * @param UrlGeneratorInterface $router
     * @param SecurityFacade        $securityFacade
     */
    public function __construct(
        ConfigManager $configManager,
        FieldTypeHelper $fieldTypeHelper,
        DateTimeFormatter $dateTimeFormatter,
        UrlGeneratorInterface $router,
        SecurityFacade $securityFacade
    ) {
        parent::__construct($configManager, $fieldTypeHelper, $dateTimeFormatter, $router);

        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function filterFields(ConfigInterface $config)
    {
        if (parent::filterFields($config)) {
            $organizationConfigProvider = $this->configManager->getProvider('organization');
            $organizationConfig         = $organizationConfigProvider->getConfigById($config->getId());

            // skip field if it's not configured for current organization
            $applicable = $organizationConfig->get('applicable', false, false);
            return
                $applicable
                && (
                    $applicable['all']
                    || in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])
                );
        }

        return false;
    }
}
