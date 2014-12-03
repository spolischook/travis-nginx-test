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
    }
}
