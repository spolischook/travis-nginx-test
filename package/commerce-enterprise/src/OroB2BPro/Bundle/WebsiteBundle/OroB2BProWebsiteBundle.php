<?php

namespace OroB2BPro\Bundle\WebsiteBundle;

use OroB2BPro\Bundle\WebsiteBundle\DependencyInjection\OroB2BProWebsiteExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BProWebsiteBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroB2BProWebsiteExtension();
    }
}
