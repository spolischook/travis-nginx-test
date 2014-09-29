<?php

namespace OroPro\Bundle\SecurityBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler\EntityAclCompilerPass;

class OroProSecurityBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EntityAclCompilerPass());
    }
}
