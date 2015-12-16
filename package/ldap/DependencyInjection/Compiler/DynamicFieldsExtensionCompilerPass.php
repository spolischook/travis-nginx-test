<?php

namespace OroCRMPro\Bundle\LDAPBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DynamicFieldsExtensionCompilerPass implements CompilerPassInterface
{
    const BASE_SERVICE_ID = 'oro_entity_extend.twig.extension.dynamic_fields.base';
    const SERVICE_ID = 'oro_entity_extend.twig.extension.dynamic_fields';

    const DECORATOR_CLASS = 'OroCRMPro\Bundle\LDAPBundle\Twig\LdapDynamicFieldsExtension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::SERVICE_ID)) {
            $definition = $container->getDefinition(self::SERVICE_ID);
            $container->setDefinition(self::BASE_SERVICE_ID, clone $definition);

            $definition->setClass(self::DECORATOR_CLASS);
            $definition->setArguments(
                [
                    $container->getDefinition(self::BASE_SERVICE_ID),
                    $container->getDefinition('oro_security.security_facade')
                ]
            );
        }
    }
}
