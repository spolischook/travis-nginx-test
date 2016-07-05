<?php

namespace OroB2BPro\Bundle\OrderBundle;

use OroB2BPro\Bundle\OrderBundle\DependencyInjection\OroB2BProOrderExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BProOrderBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProOrderExtension();
    }
}
