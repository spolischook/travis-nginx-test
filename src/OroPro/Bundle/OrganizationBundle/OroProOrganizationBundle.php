<?php

namespace OroPro\Bundle\OrganizationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OroProOrganizationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
