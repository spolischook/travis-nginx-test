<?php

namespace OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const DATETIME_FORMAT_EXTENSION_SERVICE_KEY = 'oropro_organization_config.twig.date_time_organization';
    const DATERANGE_FORMAT_EXTENSION_SERVICE_KEY = 'oropro_organization_config.twig.daterange_format_organization';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
        ) {
            // register an twig extension implements filter
            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            if ($container->hasDefinition(self::DATETIME_FORMAT_EXTENSION_SERVICE_KEY)) {
                $rendererDef->addMethodCall(
                    'addExtension',
                    [new Reference(self::DATETIME_FORMAT_EXTENSION_SERVICE_KEY)]
                );
            }
            if ($container->hasDefinition(self::DATERANGE_FORMAT_EXTENSION_SERVICE_KEY)) {
                $rendererDef->addMethodCall(
                    'addExtension',
                    [new Reference(self::DATERANGE_FORMAT_EXTENSION_SERVICE_KEY)]
                );
            }
        }
    }
}
