<?php

namespace OroB2BPro\Bundle\WarehouseBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2BPro\Bundle\WarehouseBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use OroB2BPro\Bundle\WarehouseBundle\DependencyInjection\OroB2BProWarehouseExtension;

class OroB2BProWarehouseBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }

    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProWarehouseExtension();
    }
}
