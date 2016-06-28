<?php

namespace OroPro\Bundle\OrganizationConfigBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler\ConfigurationLabelFallbackPass;

class OroProOrganizationConfigBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationLabelFallbackPass());
    }
}
