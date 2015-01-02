<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use OroPro\Bundle\OrganizationBundle\Form\Type\OrganizationLabelType;

class OrganizationLabelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationLabelType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new OrganizationLabelType();
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization_label', $this->formType->getName());
    }
}
