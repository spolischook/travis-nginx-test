<?php
namespace OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideEntityAclExtensionPass implements CompilerPassInterface
{
    const ACL_EXTENSION_SERVICE = 'oro_security.acl.extension.entity';

    const SYSTEM_MODE_ORG_PROVIDER = 'oropro_organization.system_mode_org_provider';

    const SEARCH_ACL_HELPER = 'oro_security.search.acl_helper';

    const USER_ACL_HANDLER_SERVICE = 'oro_user.autocomplete.user.search_acl_handler';
    const USER_ACL_HANDLER_CLASS   = 'OroPro\Bundle\SecurityBundle\Autocomplete\UserAclProHandler';

    const USER_ACL_GRID_LISTENER_SERVICE = 'oro_user.event_listener.owner_user_grid_listener';
    const USER_ACL_GRID_LISTENER_CLASS   = 'OroPro\Bundle\SecurityBundle\EventListener\OwnerUserProGridListener';

    const SQL_WALKER_BUILDER_SERVICE = 'oro_security.orm.ownership_sql_walker_builder';
    const SQL_WALKER_BUILDER_CLASS   = 'OroPro\Bundle\SecurityBundle\ORM\Walker\OwnershipProConditionDataBuilder';

    const PARAM_CONVERTER_SERVICE     = 'sensio_framework_extra.converter.doctrine.orm';
    const PARAM_CONVERTER_CLASS       = 'OroPro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamProConverter';
    const SECURITY_FACADE_SERVICE     = 'oro_security.security_facade';
    const OWNER_PROVIDER_SERVICE_LINK = 'oro_security.owner.ownership_metadata_provider.link';

    const IMPORTEXPORT_ENTITY_READER_SERVICE = 'oro_importexport.reader.entity';
    const IMPORTEXPORT_ENTITY_READER_CLASS   = 'OroPro\Bundle\SecurityBundle\ImportExport\Reader\EntityProReader';

    const OWNERSHIP_METADATA_SERVICE = 'oro_security.owner.ownership_metadata_provider';
    const OWNERSHIP_METADATA_CLASS   = 'OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider';

    const ORM_ACL_HELPER_SERVICE = 'oro_security.acl_helper';
    const NEW_ORM_ACL_HELPER_CLASS = 'OroPro\Bundle\SecurityBundle\ORM\Walker\AclHelper';
    const ORM_SHARE_CONDITION_DATA_BUILDER_SERVICE = 'oropro_security.orm.share_condition_data_builder';

    /**
     * @param ContainerBuilder $container
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process(ContainerBuilder $container)
    {
        // rewrite search_helper
        if ($container->hasDefinition(self::SEARCH_ACL_HELPER)) {
            $definition = $container->getDefinition(self::SEARCH_ACL_HELPER);
            $definition->addMethodCall('setRequest', [new Reference('request_stack')]);
            $this->setOrganizationProviderToService($definition);
        }

        // rewrite autocomplite owner_user_grid_listener
        if ($container->hasDefinition(self::USER_ACL_GRID_LISTENER_SERVICE)) {
            $definition = $container->getDefinition(self::USER_ACL_GRID_LISTENER_SERVICE);
            $definition->setClass(self::USER_ACL_GRID_LISTENER_CLASS);
            $this->setOrganizationProviderToService($definition);
        }

        // rewrite autocomplite search_acl_handler
        if ($container->hasDefinition(self::USER_ACL_HANDLER_SERVICE)) {
            $definition = $container->getDefinition(self::USER_ACL_HANDLER_SERVICE);
            $definition->setClass(self::USER_ACL_HANDLER_CLASS);
            $this->setOrganizationProviderToService($definition);
        }

        // rewrite ownership_sql_walker_builder
        if ($container->hasDefinition(self::SQL_WALKER_BUILDER_SERVICE)) {
            $definition = $container->getDefinition(self::SQL_WALKER_BUILDER_SERVICE);
            $definition->setClass(self::SQL_WALKER_BUILDER_CLASS);
            $definition->addMethodCall('setRegistry', [new Reference('doctrine')]);

            $this->setOrganizationProviderToService($definition);
        }

        // rewrite doctrine param converter
        if ($container->hasDefinition(self::PARAM_CONVERTER_SERVICE)) {
            $definition = $container->getDefinition(self::PARAM_CONVERTER_SERVICE);
            $definition->setClass(self::PARAM_CONVERTER_CLASS);
            $definition->addArgument(new Reference(self::SECURITY_FACADE_SERVICE));
            $this->setOrganizationProviderToService($definition);
            $definition->addMethodCall(
                'setMetadataProviderLink',
                [new Reference(self::OWNER_PROVIDER_SERVICE_LINK)]
            );
        }

        // rewrite importexport entity reader
        if ($container->hasDefinition(self::IMPORTEXPORT_ENTITY_READER_SERVICE)) {
            $definition = $container->getDefinition(self::IMPORTEXPORT_ENTITY_READER_SERVICE);
            $definition->setClass(self::IMPORTEXPORT_ENTITY_READER_CLASS);
            $definition->addMethodCall(
                'setSecurityFacade',
                [new Reference(self::SECURITY_FACADE_SERVICE)]
            );
        }

        // rewrite ownership_metadata_provider
        if ($container->hasDefinition(self::OWNERSHIP_METADATA_SERVICE)) {
            $definition = $container->getDefinition(self::OWNERSHIP_METADATA_SERVICE);
            $definition->setClass(self::OWNERSHIP_METADATA_CLASS);
        }

        // rewrite orm_acl_helper
        if ($container->hasDefinition(self::ORM_ACL_HELPER_SERVICE)) {
            $definition = $container->getDefinition(self::ORM_ACL_HELPER_SERVICE);
            $definition->setClass(self::NEW_ORM_ACL_HELPER_CLASS);
            $definition->addMethodCall(
                'setShareDataBuilder',
                [new Reference(self::ORM_SHARE_CONDITION_DATA_BUILDER_SERVICE)]
            );
        }
    }

    /**
     * Set organization provider to service
     *
     * @param Definition $definition
     */
    protected function setOrganizationProviderToService(Definition $definition)
    {
        $definition->addMethodCall(
            'setOrganizationProvider',
            [new Reference(self::SYSTEM_MODE_ORG_PROVIDER)]
        );
    }
}
