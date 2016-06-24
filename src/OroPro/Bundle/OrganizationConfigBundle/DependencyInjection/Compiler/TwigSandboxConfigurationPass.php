<?php

namespace OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const DATETIME_ORG_FORMAT_EXTENSION_SERVICE_KEY = 'oropro_organization_config.twig.date_time_organization';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
            && $container->hasDefinition(self::DATETIME_ORG_FORMAT_EXTENSION_SERVICE_KEY)
        ) {
            // register 'oro_format_datetime_organization' filter
            $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
            $filters = $securityPolicyDef->getArgument(1);
            $filters = array_merge($filters, ['oro_format_datetime_organization']);
            $securityPolicyDef->replaceArgument(1, $filters);

            // register 'calendar_date_range_organization' function
            $functions = $securityPolicyDef->getArgument(4);
            $functions = array_merge($functions, ['calendar_date_range_organization']);
            $securityPolicyDef->replaceArgument(4, $functions);

            // register an twig extension implements filter
            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            $rendererDef->addMethodCall(
                'addExtension',
                [new Reference(self::DATETIME_ORG_FORMAT_EXTENSION_SERVICE_KEY)]
            );
        }
    }
}
