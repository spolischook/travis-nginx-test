<?php

namespace Oro\Bundle\PricingProBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\PricingProBundle\DependencyInjection\OroPricingProExtension;
use Oro\Bundle\PricingProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OroPricingProBundle extends Bundle
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
        return new OroPricingProExtension();
    }
}
