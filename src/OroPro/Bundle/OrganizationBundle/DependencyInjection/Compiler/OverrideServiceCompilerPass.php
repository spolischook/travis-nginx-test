<?php

namespace OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        /**
         * Override Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension
         * Extension is responsible for columns of custom fields on grids
         */
        $serviceId = 'oro_entity_extend.datagrid.extension.dynamic_fields';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Grid\DynamicFieldsExtension');
            $definition->addArgument($container->getDefinition('oro_security.security_facade'));
        }

        /**
         * Override Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension
         * Extension is responsible for custom fields on view pages
         */
        $serviceId = 'oro_entity_extend.twig.extension.dynamic_fields';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Twig\DynamicFieldsExtension');
            $definition->addArgument($container->getDefinition('oro_security.security_facade'));
        }

        /**
         * Override Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsExtension
         * Extension is responsible for custom fields on edit pages
         */
        $serviceId = 'oro_entity_extend.form.extension.dynamic_fields';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Form\Extension\DynamicFieldsExtension');
            $definition->addArgument($container->getDefinition('oro_security.security_facade'));
        }

        $serviceId = 'oro_organization.form.extension.organization';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->addMethodCall('setSecurityFacade', [$container->getDefinition('oro_security.security_facade')]);
            $definition->addMethodCall(
                'setOrganizationProvider',
                [new Reference('oropro_organization.system_mode_org_provider')]
            );
            $definition->addMethodCall(
                'setDoctrineHelper',
                [new Reference('oro_entity.doctrine_helper')]
            );
        }

        $serviceId = 'oro_organization.form.extension.owner';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Form\Extension\OwnerProFormExtension');
            $definition->addMethodCall(
                'setOrganizationProvider',
                [new Reference('oropro_organization.system_mode_org_provider')]
            );
        }
    }
}
