<?php

namespace OroPro\Bundle\OrganizationConfigBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler\ConfigurationLabelFallbackPass;
use OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroProOrganizationConfigBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new ConfigurationLabelFallbackPass());
    }
}
