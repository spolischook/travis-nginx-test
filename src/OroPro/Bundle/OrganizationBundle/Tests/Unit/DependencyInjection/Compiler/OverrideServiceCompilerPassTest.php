<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\DependencyInjection\Compiler;

use OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OverrideServiceCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $containerMock->expects($this->exactly(13))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_email.mailbox.manager'),
                    $this->equalTo('oro_email.listener.datagrid.mailbox_grid'),
                    $this->equalTo('oro_email.provider.email_recipients.helper'),
                    $this->equalTo('oro_entity_extend.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.form.extension.dynamic_fields'),
                    $this->equalTo('oro_organization.form.extension.organization'),
                    $this->equalTo('oro_organization.form.extension.owner'),
                    $this->equalTo('oro_report.listener.navigation_listener'),
                    $this->equalTo('oro_organization.form.type.business_unit'),
                    $this->equalTo('oro_windows.twig.extension'),
                    $this->equalTo('oro_dashboard.widget_config_value.widget_business_unit_select.converter'),
                    $this->equalTo('oro_organization.form.type.organizations_select')
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
            ->expects($this->exactly(10))
            ->method('setClass')
            ->with(
                $this->logicalOr(
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Entity\Manager\MailboxManager'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\EventListener\MailboxGridListener'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Provider\EmailRecipientsHelper'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Grid\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Twig\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Extension\DynamicFieldsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Extension\OwnerProFormExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Form\Type\BusinessUnitProType'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Twig\WindowsExtension'),
                    $this->equalTo('OroPro\Bundle\OrganizationBundle\Provider\WidgetBusinessUnitSelectConverter')
                )
            )
            ->will($this->returnSelf());
        $definition
            ->expects($this->exactly(4))
            ->method('addArgument');

        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(13))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_email.mailbox.manager'),
                    $this->equalTo('oro_email.listener.datagrid.mailbox_grid'),
                    $this->equalTo('oro_email.provider.email_recipients.helper'),
                    $this->equalTo('oro_entity_extend.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.form.extension.dynamic_fields'),
                    $this->equalTo('oro_organization.form.extension.organization'),
                    $this->equalTo('oro_organization.form.extension.owner'),
                    $this->equalTo('oro_report.listener.navigation_listener'),
                    $this->equalTo('oro_organization.form.type.business_unit'),
                    $this->equalTo('oro_windows.twig.extension'),
                    $this->equalTo('oro_dashboard.widget_config_value.widget_business_unit_select.converter'),
                    $this->equalTo('oro_organization.form.type.organizations_select')
                )
            )
            ->will($this->returnValue(true));

        $containerMock->expects($this->exactly(17))
            ->method('getDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_email.mailbox.manager'),
                    $this->equalTo('oro_email.listener.datagrid.mailbox_grid'),
                    $this->equalTo('oro_email.provider.email_recipients.helper'),
                    $this->equalTo('oro_entity_extend.datagrid.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.twig.extension.dynamic_fields'),
                    $this->equalTo('oro_entity_extend.form.extension.dynamic_fields'),
                    $this->equalTo('oro_security.security_facade'),
                    $this->equalTo('oro_organization.form.extension.organization'),
                    $this->equalTo('oro_organization.form.extension.owner'),
                    $this->equalTo('oro_report.listener.navigation_listener'),
                    $this->equalTo('oro_organization.form.type.business_unit'),
                    $this->equalTo('oro_windows.twig.extension'),
                    $this->equalTo('security.context'),
                    $this->equalTo('oro_dashboard.widget_config_value.widget_business_unit_select.converter'),
                    $this->equalTo('oro_organization.form.type.organizations_select')
                )
            )

            ->will($this->returnValue($definition));

        $compilerPass = new OverrideServiceCompilerPass();
        $compilerPass->process($containerMock);
    }
}
