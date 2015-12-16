<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroPro\Bundle\OrganizationBundle\Form\Type\IsGlobalType;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class IsGlobalTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var IsGlobalType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    public function setUp()
    {
        $this->helper = $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type   = new IsGlobalType($this->helper);
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization_is_global', $this->type->getName());
    }

    /**
     * @dataProvider buildViewProvider
     * @param bool $isGlobalOrganizationExists
     * @param int  $formData
     * @param bool $expectedDisabled
     */
    public function testBuildView($isGlobalOrganizationExists, $formData, $expectedDisabled)
    {
        $view = new FormView();
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper->expects($this->once())->method('isGlobalOrganizationExists')
            ->willReturn($isGlobalOrganizationExists);
        $form->expects($this->once())->method('getViewData')->willReturn($formData);

        $this->type->buildView($view, $form, []);

        $this->assertEquals($expectedDisabled, $view->vars['disabled']);
    }

    public function buildViewProvider()
    {
        return [
            [false, 0, false],
            [false, 1, false],
            [true, 0, true],
            [true, 1, false],
        ];
    }

    /**
     * @dataProvider defaultOptionsProvider
     * @param mixed $value
     * @param int   $globalOrgId
     * @param int   $entityyId
     * @param bool  $isExpectsViolation
     */
    public function testSetDefaultOptions($value, $globalOrgId, $entityyId, $isExpectsViolation)
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);
        $result = $resolver->resolve([]);

        $this->assertEquals('oropro.organization.form.is_global', $result['tooltip']);
        $this->assertFalse($result['empty_value']);
        $this->assertEquals(['No', 'Yes'], $result['choices']);


        // check validator
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->helper->expects($this->once())->method('getGlobalOrganizationId')->willReturn($globalOrgId);
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        if ($value == 1) {
            $context->expects($this->once())->method('getRoot')->willReturn($form);
            $testOrg = new GlobalOrganization();
            $testOrg->setId($entityyId);
            $form->expects($this->once())->method('getData')->willReturn($testOrg);
        }
        if ($isExpectsViolation) {
            $context->expects($this->once())->method('addViolation')->with(IsGlobalType::INVALID_GLOBAL_MESSAGE);
        } else {
            $context->expects($this->never())->method('addViolation');
        }

        call_user_func($result['constraints']->methods[0], $value, $context);
    }

    public function defaultOptionsProvider()
    {
        return [
            'data without value, same orgs'         => [null, 12, 12, false],
            'data without value, different orgs'    => [null, 12, 10, false],
            'set non global org, same orgs'         => [0, 12, 12, false],
            'set global org, same orgs'             => [1, 12, 12, false],
            'set non global org, different orgs'    => [0, 12, 10, false],
            'set global global org, different orgs' => [1, 12, 10, true],
        ];
    }
}
