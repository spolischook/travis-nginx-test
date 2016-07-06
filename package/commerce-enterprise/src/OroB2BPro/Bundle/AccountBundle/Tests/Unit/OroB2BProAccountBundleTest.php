<?php

namespace OroB2BPro\Bundle\AccountBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2BPro\Bundle\AccountBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use OroB2BPro\Bundle\AccountBundle\DependencyInjection\OroB2BProAccountExtension;
use OroB2BPro\Bundle\AccountBundle\OroB2BProAccountBundle;

class OroB2BProAccountBundleTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroB2BProAccountBundle */
    protected $bundle;

    protected function setUp()
    {
        $this->bundle = new OroB2BProAccountBundle();
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
        $this->assertInstanceOf(OroB2BProAccountExtension::class, $this->bundle->getContainerExtension());
    }
}
