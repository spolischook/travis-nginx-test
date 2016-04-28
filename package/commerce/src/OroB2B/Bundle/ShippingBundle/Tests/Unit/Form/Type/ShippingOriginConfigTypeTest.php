<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginConfigType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginType;

class ShippingOriginConfigTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShippingOriginConfigType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new ShippingOriginConfigType();
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingOriginConfigType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ShippingOriginType::NAME, $this->formType->getParent());
    }

    public function testFinishViewParentScopeValues()
    {
        $childView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        $mockFormView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();
        $mockFormView->children = [$childView];

        $mockParentScopeValueForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mockParentScopeValueForm->expects($this->once())->method('getData')->willReturn('data');

        $mockParentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mockParentForm->expects($this->once())->method('has')->with('use_parent_scope_value')->willReturn(true);
        $mockParentForm->expects($this->once())
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($mockParentScopeValueForm);

        $mockFormInterface = $this->getMock('Symfony\Component\Form\FormInterface');
        $mockFormInterface->expects($this->once())->method('getParent')->willReturn($mockParentForm);

        $this->formType->finishView($mockFormView, $mockFormInterface, []);

        $this->assertEquals(
            [
                'value' => null,
                'attr' => [],
                'use_parent_scope_value' => 'data'
            ],
            $childView->vars
        );
    }
}
