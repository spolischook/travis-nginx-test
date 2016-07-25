<?php

namespace Oro\Bundle\OrderProBundle;

use Oro\Bundle\OrderProBundle\DependencyInjection\OroOrderProExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroOrderProBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroOrderProExtension();
    }
}
