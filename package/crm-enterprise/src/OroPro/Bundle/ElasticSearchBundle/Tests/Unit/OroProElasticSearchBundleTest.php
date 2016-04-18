<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit;

use OroPro\Bundle\ElasticSearchBundle\OroProElasticSearchBundle;

class OroProElasticSearchBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $bundle = new OroProElasticSearchBundle();

        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    'OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler\ElasticSearchProviderPass'
                )
            );

        $bundle->build($container);
    }
}
