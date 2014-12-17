<?php
namespace OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideEntityAclExtensionPass implements CompilerPassInterface
{
    const ACL_EXTENSION_SERVICE         = 'oro_security.acl.extension.entity';
    const SECURITY_CONTEXT_SERVICE_LINK = 'security.context.link';

    /**
     * Add context link service to the entity ACL extension service
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::ACL_EXTENSION_SERVICE)) {
            $definition = $container->getDefinition(self::ACL_EXTENSION_SERVICE);
            $definition->addMethodCall('setContextLink', [new Reference(self::SECURITY_CONTEXT_SERVICE_LINK)]);
        }

        // rewrite search_listener
        if ($container->hasDefinition('oro_security.listener.search_listener')) {
            $definition = $container->getDefinition('oro_security.listener.search_listener');
            $definition->addMethodCall(
                'setOrganizationIdProvider',
                [new Reference('oropro_organization.organization_id_provider')]
            );
        }

        // rewrite autocomplite search_acl_handler
        if ($container->hasDefinition('oro_user.autocomplete.user.search_acl_handler')) {
            $definition = $container->getDefinition('oro_user.autocomplete.user.search_acl_handler');
            $definition->setClass('OroPro\Bundle\SecurityBundle\Autocomplete\UserAclProHandler');
            $definition->addMethodCall(
                'setOrganizationIdProvider',
                [new Reference('oropro_organization.organization_id_provider')]
            );
        }

        // rewrite ownership_sql_walker_builder
        if ($container->hasDefinition('oro_security.orm.ownership_sql_walker_builder')) {
            $definition = $container->getDefinition('oro_security.orm.ownership_sql_walker_builder');
            $definition->setClass('OroPro\Bundle\SecurityBundle\ORM\Walker\OwnershipProConditionDataBuilder');
            $definition->addMethodCall(
                'setOrganizationIdProvider',
                [new Reference('oropro_organization.organization_id_provider')]
            );
        }

        // rewrite doctrine param converter
        if ($container->hasDefinition('sensio_framework_extra.converter.doctrine.orm')) {
            $definition = $container->getDefinition('sensio_framework_extra.converter.doctrine.orm');
            $definition->setClass('OroPro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamProConverter');
            $definition->addArgument(new Reference('oro_security.security_facade'));
            $definition->addMethodCall(
                'setOrganizationIdProvider',
                [new Reference('oropro_organization.organization_id_provider')]
            );
            $definition->addMethodCall(
                'setMetadataProviderLink',
                [new Reference('oro_security.owner.ownership_metadata_provider.link')]
            );
        }
    }
}
