<?php

namespace Oro\Bundle\SaleProBundle;

use Oro\Bundle\SaleProBundle\DependencyInjection\OroSaleProExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSaleProBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroSaleProExtension();
    }
}
