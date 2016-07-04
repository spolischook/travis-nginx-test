<?php

namespace OroB2BPro\Bundle\InvoiceBundle;

use OroB2BPro\Bundle\InvoiceBundle\DependencyInjection\OroB2BProInvoiceExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BProInvoiceBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProInvoiceExtension();
    }
}
