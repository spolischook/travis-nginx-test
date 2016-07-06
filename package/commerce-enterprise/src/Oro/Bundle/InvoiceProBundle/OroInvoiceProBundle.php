<?php

namespace Oro\Bundle\InvoiceProBundle;

use Oro\Bundle\InvoiceProBundle\DependencyInjection\OroInvoiceProExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroInvoiceProBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroInvoiceProExtension();
    }
}
