<?php

namespace OroPro\Bundle\organizationBundle\Tests\Unit\Form\Type;

use OroPro\Bundle\OrganizationBundle\Form\Type\OrganizationType;

class OrganizationTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new OrganizationType();
    }

    /**
     * @param array $widgets
     *
     * @dataProvider formTypeProvider
     */
    public function testBuildForm(array $widgets)
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('add')
            ->will($this->returnSelf());

        foreach ($widgets as $key => $widget) {
            $builder->expects($this->at($key))
                ->method('add')
                ->with($this->equalTo($widget))
                ->will($this->returnSelf());
        }

        $this->formType->buildForm($builder, []);
    }

    public function formTypeProvider()
    {
        return [
            'all' => [
                'widgets' => [
                    'enabled',
                    'name',
                    'description'
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization', $this->formType->getName());
    }
}
