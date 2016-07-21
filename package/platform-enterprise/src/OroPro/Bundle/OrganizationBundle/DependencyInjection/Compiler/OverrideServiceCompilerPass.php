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
         * This override is responsible for making all mailboxes available to users logged under global organization.
         */
        $serviceId = 'oro_email.mailbox.manager';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Entity\Manager\MailboxManager');
        }

        /**
         * Shows mailboxes for all organizations in system configuration if logged under global organization
         */
        $serviceId = 'oro_email.listener.datagrid.mailbox_grid';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\EventListener\MailboxGridListener');
            $definition->addMethodCall('setSecurityFacade', [new Reference('oro_security.security_facade')]);
        }

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
            $definition->addMethodCall(
                'setRouter',
                [new Reference('router')]
            );
        }

        /**
         * Override Oro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator
         * In case of System access mode, we should take organization from the entity
         */
        $serviceId = 'oro_organization.validator.owner';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator');
        }

        /**
         * Shows organization in filters of grid if logged under global organization
         */
        $serviceId = 'oro_organization.listener.choice_tree_filter_load_data_listener';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\EventListener\ChoiceTreeFilterLoadDataListener');
            $definition->addMethodCall('setSecurityFacade', [new Reference('oro_security.security_facade')]);
        }

        $this->overrideOrganizationsSelect($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function overrideOrganizationsSelect(ContainerBuilder $container)
    {
        $serviceId = 'oro_organization.form.type.organizations_select';
        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $definition = $container->getDefinition($serviceId);
        $definition->addMethodCall(
            'setOrganizationProHelper',
            [new Reference('oropro_organization.helper')]
        );
    }
}
