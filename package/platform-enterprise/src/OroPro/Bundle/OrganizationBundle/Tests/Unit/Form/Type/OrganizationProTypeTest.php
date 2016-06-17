<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilder;

use OroPro\Bundle\OrganizationBundle\Form\Type\OrganizationProType;

class OrganizationProTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationProType */
    protected $formType;

    protected function setUp()
    {
        $securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()->getMock();
        $this->formType  = new OrganizationProType($securityContext);
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testBuildForm()
    {
        $dispatcher  = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()->getMock();
        $builder     = new FormBuilder(null, null, $dispatcher, $formFactory);

        $this->formType->buildForm($builder, []);

        $this->assertTrue($builder->has('appendUsers'));
        $this->assertTrue($builder->has('removeUsers'));
    }
}
