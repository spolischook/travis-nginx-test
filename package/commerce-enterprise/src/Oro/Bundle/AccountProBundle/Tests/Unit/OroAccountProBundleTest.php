<?php

namespace Oro\Bundle\AccountProBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\AccountProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Oro\Bundle\AccountProBundle\DependencyInjection\OroAccountProExtension;
use Oro\Bundle\AccountProBundle\OroAccountProBundle;

class OroB2BProAccountBundleTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroAccountProBundle */
    protected $bundle;

    protected function setUp()
    {
        $this->bundle = new OroAccountProBundle();
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

    public function testGetContainerExtension()
    {
        $this->assertInstanceOf(OroAccountProExtension::class, $this->bundle->getContainerExtension());
    }
}
