<?php

namespace OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclConfigurationPass;

class OroProAclConfigurationPass implements CompilerPassInterface
{
    const NEW_ACL_SECURITY_ID_STRATEGY_CLASS = 'oropro_security.acl.security_identity_retrieval_strategy.class';
    const DEFAULT_ACL_SECURITY_ID_STRATEGY_CLASS = 'security.acl.security_identity_retrieval_strategy.class';
    const NEW_ACL_PROVIDER_CLASS = 'OroPro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider';
    const EVENT_DISPATCHER_LINK = 'event_dispatcher';

    const BU_GRID_LISTENER_SERVICE = 'oro_organization.event.business_unit_grid_listener';
    const BU_GRID_LISTENER_TAG_NAME = 'kernel.event_listener';
    const BU_GRID_LISTENER_TAG_EVENT = 'oro_datagrid.datagrid.build.before.share-with-business-units-datagrid';
    const BU_GRID_LISTENER_TAG_METHOD = 'onBuildBefore';

    const USER_DELETE_HANDLER_SERVICE = 'oro_user.handler.delete';
    const NEW_USER_DELETE_HANDLER_SERVICE_CLASS = 'OroPro\Bundle\SecurityBundle\Form\Handler\UserDeleteHandler';
    const SHARE_PROVIDER_SERVICE = 'oropro_security.provider.share_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->configureDefaultAclProvider($container);
        $this->configureDefaultAclVoter($container);
        $this->configureBUGridListener($container);
        $this->configureUserDeleteHandler($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureDefaultAclProvider(ContainerBuilder $container)
    {
        if ($container->hasDefinition(AclConfigurationPass::DEFAULT_ACL_PROVIDER)) {
            $providerDef = $container->getDefinition(AclConfigurationPass::DEFAULT_ACL_PROVIDER);
            $providerDef->setClass(self::NEW_ACL_PROVIDER_CLASS);
            $providerDef->addMethodCall('setEventDispatcher', [new Reference(self::EVENT_DISPATCHER_LINK)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureDefaultAclVoter(ContainerBuilder $container)
    {
        if ($container->hasDefinition(AclConfigurationPass::DEFAULT_ACL_VOTER)) {
            // substitute ACL Security Identity Retrieval Strategy
            if ($container->hasParameter(self::NEW_ACL_SECURITY_ID_STRATEGY_CLASS)) {
                $container->setParameter(
                    self::DEFAULT_ACL_SECURITY_ID_STRATEGY_CLASS,
                    $container->getParameter(self::NEW_ACL_SECURITY_ID_STRATEGY_CLASS)
                );
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureBUGridListener(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::BU_GRID_LISTENER_SERVICE)) {
            $definition = $container->getDefinition(self::BU_GRID_LISTENER_SERVICE);
            $definition->addTag(self::BU_GRID_LISTENER_TAG_NAME, [
                'event' => self::BU_GRID_LISTENER_TAG_EVENT,
                'method' => self::BU_GRID_LISTENER_TAG_METHOD,
            ]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureUserDeleteHandler(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::USER_DELETE_HANDLER_SERVICE)) {
            $definition = $container->getDefinition(self::USER_DELETE_HANDLER_SERVICE);
            $definition->setClass(self::NEW_USER_DELETE_HANDLER_SERVICE_CLASS);
            $definition->addMethodCall('setShareProvider', [new Reference(self::SHARE_PROVIDER_SERVICE)]);
        }
    }
}
