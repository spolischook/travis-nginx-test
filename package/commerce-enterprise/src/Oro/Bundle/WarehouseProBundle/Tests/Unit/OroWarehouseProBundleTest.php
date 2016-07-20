<?php

namespace Oro\Bundle\WarehouseProBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WarehouseProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Oro\Bundle\WarehouseProBundle\OroWarehouseProBundle;

class OroWarehouseProBundleTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroWarehouseProBundle */
    protected $bundle;

    protected function setUp()
    {
        $this->bundle = new OroWarehouseProBundle();
    }

    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(OverrideServiceCompilerPass::class));

        $this->bundle->build($containerBuilder);
    }
}
