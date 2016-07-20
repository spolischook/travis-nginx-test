<?php

namespace Oro\Bundle\WarehouseProBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WarehouseProBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Oro\Bundle\WarehouseProBundle\ImportExport\Reader\ProInventoryLevelReader;

class OverrideServiceCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(1))
            ->method('hasDefinition')
            ->with(
                $this->equalTo('orob2b_warehouse.importexport.reader.inventory_level')
            )
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
            ->expects($this->exactly(1))
            ->method('setClass')
            ->with(
                $this->equalTo(ProInventoryLevelReader::class)
            )
            ->will($this->returnSelf());

        $definition
            ->expects($this->once())
            ->method('addMethodCall')
            ->with('setSecurityFacade', $this->isType('array'));

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(1))
            ->method('hasDefinition')
            ->with(
                $this->equalTo('orob2b_warehouse.importexport.reader.inventory_level')
            )
            ->will($this->returnValue(true));

        $containerMock->expects($this->exactly(1))
            ->method('getDefinition')
            ->with(
                $this->equalTo('orob2b_warehouse.importexport.reader.inventory_level')
            )
            ->will($this->returnValue($definition));

        $compilerPass = new OverrideServiceCompilerPass();
        $compilerPass->process($containerMock);
    }
}
