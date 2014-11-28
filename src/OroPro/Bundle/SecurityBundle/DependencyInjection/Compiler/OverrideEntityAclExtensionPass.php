<?php
namespace OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideEntityAclExtensionPass implements CompilerPassInterface
{
    const ACL_EXTENSION_SERVICE = 'oro_security.acl.extension.entity';
    const SECURITY_FACADE_SERVICE = 'oro_security.security_facade';

    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(self::ACL_EXTENSION_SERVICE);
        $definition->addMethodCall('setSecurityFacade', [new Reference(self::SECURITY_FACADE_SERVICE)]);
    }
}
