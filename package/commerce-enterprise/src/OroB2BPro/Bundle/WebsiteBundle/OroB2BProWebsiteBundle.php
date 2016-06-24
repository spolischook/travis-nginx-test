<?php

namespace OroB2BPro\Bundle\WebsiteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2BPro\Bundle\WebsiteBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OroB2BProWebsiteBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
