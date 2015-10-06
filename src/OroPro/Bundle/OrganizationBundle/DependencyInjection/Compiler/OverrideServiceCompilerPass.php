<?php

namespace OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process(ContainerBuilder $container)
    {
        /**
         * Extension is responsible for showing organization in global organizaton in recipients autocomplete
         */
        $serviceId = 'oro_email.provider.email_recipients.helper';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Provider\EmailRecipientsHelper');
            $definition->addMethodCall('setSecurityFacade', [new Reference('oro_security.security_facade')]);
        }

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
            $definition->addArgument(new Reference('oropro_organization.system_mode_org_provider'));
        }

        /**
         * Override Oro\Bundle\OrganizationBundle\Form\Extension\OrganizationFormExtension
         * Add security facade, system access mode organization provider and doctrine helper
         */
        $serviceId = 'oro_organization.form.extension.organization';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->addMethodCall(
                'setSecurityFacade',
                [$container->getDefinition('oro_security.security_facade')]
            );
            $definition->addMethodCall(
                'setOrganizationProvider',
                [new Reference('oropro_organization.system_mode_org_provider')]
            );
            $definition->addMethodCall(
                'setDoctrineHelper',
                [new Reference('oro_entity.doctrine_helper')]
            );
        }

        /**
         * Override Oro\Bundle\OrganizationBundle\Form\Extension\OwnerFormExtension
         * Add system access mode organization provider
         */
        $serviceId = 'oro_organization.form.extension.owner';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Form\Extension\OwnerProFormExtension');
            $definition->addMethodCall(
                'setOrganizationProvider',
                [new Reference('oropro_organization.system_mode_org_provider')]
            );
        }

        /**
         * Override Oro\Bundle\ReportBundle\EventListener\NavigationListener
         * Add system access mode organization provider
         */
        $serviceId = 'oro_report.listener.navigation_listener';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->addMethodCall(
                'setOrganizationProvider',
                [new Reference('oropro_organization.system_mode_org_provider')]
            );
        }

        /**
         * Override Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitType.
         * Add system access mode organization provider
         */
        $serviceId = 'oro_organization.form.type.business_unit';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Form\Type\BusinessUnitProType');
            $definition->addMethodCall(
                'setOrganizationProvider',
                [new Reference('oropro_organization.system_mode_org_provider')]
            );
        }

        /**
         * Override Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension
         * Dialog two step form render
         */
        $serviceId = 'oro_windows.twig.extension';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Twig\WindowsExtension');
            $definition->addArgument($container->getDefinition('security.context'));
            $definition->addArgument(new Reference('doctrine.orm.entity_manager'));
            $definition->addMethodCall(
                'setRouter',
                [new Reference('router')]
            );
        }

        /**
         * Override Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager
         * Add logic to get business unit name if organization is global
         */
        $serviceId = 'oro_organization.business_unit_manager';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager');
        }
    }
}
