<?php

namespace Oro\Bundle\AccountProBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\AccountProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Oro\Bundle\AccountProBundle\DependencyInjection\OroAccountProExtension;

class OroAccountProBundle extends Bundle
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
        return new OroAccountProExtension();
    }
}
