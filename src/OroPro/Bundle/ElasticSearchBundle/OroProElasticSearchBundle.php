<?php

namespace OroPro\Bundle\ElasticSearchBundle;

use OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler\ElasticSearchProviderPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroProElasticSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ElasticSearchProviderPass());
    }
}
