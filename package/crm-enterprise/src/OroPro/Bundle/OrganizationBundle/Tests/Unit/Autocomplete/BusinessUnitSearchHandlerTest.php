<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\OrganizationBundle\Autocomplete\BusinessUnitSearchHandler;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\ReflectionUtil;

class BusinessUnitSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const BUSINESS_UNIT_NAME = 'bu_test_name';
    const ORGANIZATION_NAME = 'organization_test_name';

    /**
     * @var SearchHandler
     */
    protected $searchHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $entityName = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
        $this->searchHandler = new BusinessUnitSearchHandler($entityName, ['name'], $this->securityFacade);
        ReflectionUtil::setPrivateProperty($this->searchHandler, 'idFieldName', 'id');
    }

    public function testConvertItem()
    {
        $organization = new Organization();
        $organization->setName(self::ORGANIZATION_NAME);

        $businessUnitItem = new BusinessUnit();
        $businessUnitItem->setName(self::BUSINESS_UNIT_NAME)
            ->setOrganization($organization);
        ReflectionUtil::setId($businessUnitItem, 1);

        $globalOrganization = new GlobalOrganization();
        $globalOrganization->setIsGlobal(true);

        $this->securityFacade
            ->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($globalOrganization));

        $result = $this->searchHandler->convertItem($businessUnitItem);

        $expectedResult = ['id' => 1, 'name' => self::BUSINESS_UNIT_NAME . ' (' . self::ORGANIZATION_NAME . ')'];
        $this->assertEquals($expectedResult, $result);

        $globalOrganization->setIsGlobal(false);
        $result = $this->searchHandler->convertItem($businessUnitItem);
        $expectedResult = ['id' => 1, 'name' => self::BUSINESS_UNIT_NAME];
        $this->assertEquals($expectedResult, $result);
    }
}
