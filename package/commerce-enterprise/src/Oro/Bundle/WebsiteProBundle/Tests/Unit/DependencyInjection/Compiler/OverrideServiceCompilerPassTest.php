<?php

namespace Oro\Bundle\WebsiteProBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WebsiteProBundle\Provider\WebsiteProvider;
use Oro\Bundle\WebsiteProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OverrideServiceCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('orob2b_website.website.provider'))
            ->will($this->returnValue(false));

        $containerMock
            ->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new OverrideServiceCompilerPass();
        $compilerPass->process($containerMock);
    }

    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->setMethods([])
            ->getMock();

        $definition
            ->expects($this->once())
            ->method('setClass')
            ->with($this->equalTo(WebsiteProvider::class))
            ->will($this->returnSelf());

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('orob2b_website.website.provider'))
            ->will($this->returnValue(true));

        $containerMock->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo('orob2b_website.website.provider'))

            ->will($this->returnValue($definition));

        $compilerPass = new OverrideServiceCompilerPass();
        $compilerPass->process($containerMock);
    }
}
