<?php

namespace Oro\Bundle\AccountProBundle;

use Oro\Bundle\AccountProBundle\DependencyInjection\OroAccountProExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroAccountProBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroAccountProExtension();
    }
}
