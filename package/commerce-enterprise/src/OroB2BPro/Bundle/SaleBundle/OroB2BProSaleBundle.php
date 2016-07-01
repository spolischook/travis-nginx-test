<?php

namespace OroB2BPro\Bundle\SaleBundle;

use OroB2BPro\Bundle\SaleBundle\DependencyInjection\OroB2BProSaleExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BProSaleBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProSaleExtension();
    }
}
