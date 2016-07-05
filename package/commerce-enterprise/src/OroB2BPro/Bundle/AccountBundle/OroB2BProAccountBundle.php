<?php

namespace OroB2BPro\Bundle\AccountBundle;

use OroB2BPro\Bundle\AccountBundle\DependencyInjection\OroB2BProAccountExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BProAccountBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProAccountExtension();
    }
}
