<?php

namespace OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        /**
         * Override Oro\Bundle\EntityBundle\Grid\DynamicFieldsExtension
         */
        $serviceId = 'oro_entity.datagrid.extension.dynamic_fields';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Grid\DynamicFieldsExtension');
            $definition->addArgument($container->getDefinition('oro_security.security_facade'));
        }
    }
}
