<?php

namespace Oro\Bundle\WarehouseProBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\WarehouseProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OroWarehouseProBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
