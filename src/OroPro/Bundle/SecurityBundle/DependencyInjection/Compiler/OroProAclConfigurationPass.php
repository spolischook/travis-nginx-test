<?php

namespace OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclConfigurationPass;

class OroProAclConfigurationPass implements CompilerPassInterface
{
    const NEW_ACL_SECURITY_ID_STRATEGY_CLASS = 'oro_security.acl.security_identity_retrieval_strategy.class';
    const DEFAULT_ACL_SECURITY_ID_STRATEGY_CLASS = 'security.acl.security_identity_retrieval_strategy.class';
    const EVENT_DISPATCHER_LINK = 'event_dispatcher';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->configureDefaultAclProvider($container);
        $this->configureDefaultAclVoter($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureDefaultAclProvider(ContainerBuilder $container)
    {
        if ($container->hasDefinition(AclConfigurationPass::DEFAULT_ACL_PROVIDER)) {
            $providerDef = $container->getDefinition(AclConfigurationPass::DEFAULT_ACL_PROVIDER);
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
}
