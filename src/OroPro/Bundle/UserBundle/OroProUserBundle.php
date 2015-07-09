<?php

namespace OroPro\Bundle\UserBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroPro\Bundle\UserBundle\DependencyInjection\Compiler\UserFormTypeExtensionPass;

class OroProUserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new UserFormTypeExtensionPass());
    }
}
