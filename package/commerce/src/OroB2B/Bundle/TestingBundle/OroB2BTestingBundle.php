<?php

namespace OroB2B\Bundle\TestingBundle;

use OroB2B\Bundle\TestingBundle\DependencyInjection\OroB2BTestingExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BTestingBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroB2BTestingExtension();
    }
}
