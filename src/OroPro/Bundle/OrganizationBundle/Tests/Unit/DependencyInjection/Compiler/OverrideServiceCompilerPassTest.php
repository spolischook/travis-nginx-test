<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\DependencyInjection\Compiler;

use OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OverrideServiceCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $containerMock->expects($this->exactly(7))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_entity_extend.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.form.extension.dynamic_fields'),
                    $this->equalTo('oro_organization.form.extension.organization'),
                    $this->equalTo('oro_organization.form.extension.owner'),
                    $this->equalTo('oro_report.listener.navigation_listener'),
                    $this->equalTo('oro_organization.form.type.business_unit')
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
            ->expects($this->exactly(5))
            ->method('setClass')
            ->with(
                $this->logicalOr(
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Grid\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Twig\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Extension\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Extension\OwnerProFormExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Type\BusinessUnitProType')
                )
            )
            ->will($this->returnSelf());
        $definition
            ->expects($this->exactly(4))
            ->method('addArgument');

        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(7))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_entity_extend.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.form.extension.dynamic_fields'),
                    $this->equalTo('oro_organization.form.extension.organization'),
                    $this->equalTo('oro_organization.form.extension.owner'),
                    $this->equalTo('oro_report.listener.navigation_listener'),
                    $this->equalTo('oro_organization.form.type.business_unit')
                )
            )
            ->will($this->returnValue(true));

        $containerMock->expects($this->exactly(11))
            ->method('getDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_entity_extend.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.form.extension.dynamic_fields'),
                    $this->equalTo('oro_security.security_facade'),
                    $this->equalTo('oro_organization.form.extension.organization'),
                    $this->equalTo('oro_organization.form.extension.owner'),
                    $this->equalTo('oro_report.listener.navigation_listener'),
                    $this->equalTo('oro_organization.form.type.business_unit')
                )
            )

            ->will($this->returnValue($definition));

        $compilerPass = new OverrideServiceCompilerPass();
        $compilerPass->process($containerMock);
    }
}
