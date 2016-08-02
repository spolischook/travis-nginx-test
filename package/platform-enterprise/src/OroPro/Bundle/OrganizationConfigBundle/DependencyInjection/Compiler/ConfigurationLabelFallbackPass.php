<?php

namespace OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ConfigurationLabelFallbackPass implements CompilerPassInterface
{
    const USER_CONFIG_PROVIDER_SERVICE_ID = 'oro_user.provider.user_config_form_provider';
    const USER_CONFIG_PROVIDER_PARENT_CHECKBOX_LABEL = 'oropro.user_configuration.use_default';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::USER_CONFIG_PROVIDER_SERVICE_ID)) {
            return;
        }

        $userConfigProviderDef = $container->getDefinition(self::USER_CONFIG_PROVIDER_SERVICE_ID);
        $userConfigProviderDef->addMethodCall(
            'setParentCheckboxLabel',
            [self::USER_CONFIG_PROVIDER_PARENT_CHECKBOX_LABEL]
        );
    }
}
