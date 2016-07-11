<?php

namespace Oro\Bundle\WebsiteProBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\WebsiteProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Oro\Bundle\WebsiteProBundle\DependencyInjection\OroWebsiteProExtension;

class OroWebsiteProBundle extends Bundle
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
        return new OroWebsiteProExtension();
    }
}
