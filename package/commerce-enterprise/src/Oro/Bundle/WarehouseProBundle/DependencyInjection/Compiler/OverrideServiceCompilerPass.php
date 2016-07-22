<?php

namespace Oro\Bundle\WarehouseProBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\WarehouseProBundle\ImportExport\Reader\ProInventoryLevelReader;

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
            $definition->setClass(ProInventoryLevelReader::class);
            
            $definition->addMethodCall('setSecurityFacade', [new Reference('oro_security.security_facade')]);
        }
    }
}
