<?php

namespace OroPro\Bundle\OrganizationConfigBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroProOrganizationConfigBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
