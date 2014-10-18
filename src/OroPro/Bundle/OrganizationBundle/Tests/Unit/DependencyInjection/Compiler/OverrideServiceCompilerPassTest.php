<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\DependencyInjection\Compiler;

use OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OverrideServiceCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $containerMock->expects($this->exactly(3))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_entity.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_config.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.extension.extend_entity')
                )
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
            ->expects($this->exactly(3))
            ->method('setClass')
            ->with(
                $this->logicalOr(
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Grid\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Twig\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Extension\ExtendEntityExtension')
                )
            )
            ->will($this->returnSelf());
        $definition
            ->expects($this->exactly(3))
            ->method('addArgument');

        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(3))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_entity.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_config.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.extension.extend_entity')
                )
            )
            ->will($this->returnValue(true));

        $containerMock->expects($this->exactly(6))
            ->method('getDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_entity.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_config.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.extension.extend_entity'),
                    $this->equalTo('oro_security.security_facade')
                )
            )

            ->will($this->returnValue($definition));

        $compilerPass = new OverrideServiceCompilerPass();
        $compilerPass->process($containerMock);
    }
}
