<?php

namespace Oro\Bundle\AccountProBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\AccountProBundle\DependencyInjection\OroAccountProExtension;

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
