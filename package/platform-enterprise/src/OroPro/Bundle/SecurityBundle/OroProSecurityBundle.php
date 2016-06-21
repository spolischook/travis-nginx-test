<?php

namespace OroPro\Bundle\SecurityBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler\OverrideEntityAclExtensionPass;
use OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler\OverrideSecurityTokenFactories;
use OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler\OroProAclConfigurationPass;

class OroProSecurityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideEntityAclExtensionPass());
        $container->addCompilerPass(new OverrideSecurityTokenFactories());
        $container->addCompilerPass(new OroProAclConfigurationPass());
    }
}
