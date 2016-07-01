<?php

namespace OroB2BPro\Bundle\WarehouseBundle\DependencyInjection\Compiler;

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
        $serviceId = 'orob2b_warehouse.importexport.reader.inventory_level';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroB2BPro\Bundle\WarehouseBundle\ImportExport\Reader\ProInventoryLevelReader');
            
            $definition->addMethodCall('setSecurityFacade', [new Reference('oro_security.security_facade')]);
        }
    }
}
