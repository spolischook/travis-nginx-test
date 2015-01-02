<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use OroPro\Bundle\OrganizationBundle\Form\Type\BusinessUnitProType;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class BusinessUnitProTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var BusinessUnitProType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    public function setUp()
    {
        $businessUnitManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organizationProvider = new SystemAccessModeOrganizationProvider();

        $this->formType = new BusinessUnitProType($businessUnitManager, $this->securityFacade);
    }

    public function testGetOrganizationId()
    {
        $this->formType->setOrganizationProvider($this->organizationProvider);
        $reflection = new \ReflectionObject($this->formType);
        $method     = $reflection->getMethod('getOrganizationId');

        $method->setAccessible(true);

        $currentOrganization = new GlobalOrganization();
        $currentOrganization->setId(8);

        $selectedOrganization = new GlobalOrganization();
        $selectedOrganization->setId(5);

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($currentOrganization);
        $this->organizationProvider->setOrganization($selectedOrganization);

        $this->assertEquals(5, $method->invoke($this->formType));
    }
}
