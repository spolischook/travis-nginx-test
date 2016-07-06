<?php

namespace OroB2BPro\Bundle\AccountBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2BPro\Bundle\AccountBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use OroB2BPro\Bundle\AccountBundle\DependencyInjection\OroB2BProAccountExtension;

class OroB2BProAccountBundle extends Bundle
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
        return new OroB2BProAccountExtension();
    }
}
