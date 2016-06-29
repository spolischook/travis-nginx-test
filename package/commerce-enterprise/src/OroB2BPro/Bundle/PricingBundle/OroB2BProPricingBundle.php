<?php

namespace OroB2BPro\Bundle\PricingBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2BPro\Bundle\PricingBundle\DependencyInjection\OroB2BProPricingExtension;
use OroB2BPro\Bundle\PricingBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OroB2BProPricingBundle extends Bundle
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
        return new OroB2BProPricingExtension();
    }
}
