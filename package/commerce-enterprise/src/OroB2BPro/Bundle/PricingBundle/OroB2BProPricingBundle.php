<?php

namespace OroB2BPro\Bundle\PricingBundle;

use OroB2BPro\Bundle\PricingBundle\DependencyInjection\OroB2BProPricingExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BProPricingBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProPricingExtension();
    }
}
