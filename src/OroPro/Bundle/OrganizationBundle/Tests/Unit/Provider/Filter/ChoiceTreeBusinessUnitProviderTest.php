<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Provider\Filter;

use OroPro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider;

class ChoiceTreeBusinessUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChoiceTreeBusinessUnitProvider */
    protected $choiceTreeBusinessUnitProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceTreeBusinessUnitProvider = new ChoiceTreeBusinessUnitProvider(
            $this->registry,
            $this->securityFacade,
            $this->aclHelper
        );
    }

    public function testGetList()
    {
        $businessUnitRepository = $this->getMockBuilder('BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getQueryBuilder'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->setMethods(['getIsGlobal'])
            ->disableOriginalConstructor()
            ->getMock();

        $organization->expects($this->any())->method('getIsGlobal')->willReturn(true);

        $this->securityFacade->expects($this->any())->method('getOrganization')->willReturn($organization);

        $this->aclHelper->expects($this->any())->method('apply')->willReturn($qb);
        $businessUnitRepository->expects($this->any())->method('getQueryBuilder')->willReturn($qb);
        $qb->expects($this->any())->method('getResult')->willReturn($this->getTestBusinessUnits());

        $this->registry->expects($this->once())->method('getRepository')->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);

        $result = $this->choiceTreeBusinessUnitProvider->getList();

        $this->assertEquals($this->getExpectedData(), $result);
    }

    public function testGetEmptyList()
    {
        $businessUnitRepository = $this->getMockBuilder('BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getQueryBuilder'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper->expects($this->any())->method('apply')->willReturn($qb);
        $businessUnitRepository->expects($this->any())->method('getQueryBuilder')->willReturn($qb);
        $qb->expects($this->any())->method('getResult')->willReturn([]);

        $this->registry->expects($this->once())->method('getRepository')->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);

        $result = $this->choiceTreeBusinessUnitProvider->getList();

        $this->assertEquals([], $result);
    }

    protected function getTestBusinessUnits()
    {
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
//            ->setMethods(['getName'])
            ->disableOriginalConstructor()->getMock();
        $organization->expects($this->any())->method('getName')->willReturn('Organization 1');

        $data = [
            [
                'name' => 'Main Business Unit',
                'id' => 1,
                'owner_id' => null,
                'organization' => $organization
            ],
            [
                'name' => 'Business Unit 1',
                'id' => 2,
                'owner_id' => $this->getTestDataRootBusinessUnit(),
                'organization' => $organization
            ]
        ];

        return $this->convertTestDataToBusinessUnitEntity($data);
    }

    protected function convertTestDataToBusinessUnitEntity($data)
    {
        $response = [];
        foreach ($data as $item) {
            $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
                ->disableOriginalConstructor()
                ->getMock();
            $businessUnit->expects($this->any())->method('getId')->willReturn($item['id']);
            $businessUnit->expects($this->any())->method('getOwner')->willReturn($item['owner_id']);
            $businessUnit->expects($this->any())->method('getName')->willReturn($item['name']);
            $businessUnit->expects($this->any())->method('getOrganization')->willReturn($item['organization']);

            $response[] = $businessUnit;
        }

        return $response;
    }

    protected function getTestDataRootBusinessUnit()
    {
        $rootBusinessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $rootBusinessUnit->expects($this->any())->method('getId')->willReturn('1');
        $rootBusinessUnit->expects($this->any())->method('getOwner')->willReturn(null);
        $rootBusinessUnit->expects($this->any())->method('getName')->willReturn('Main Business Unit');

        return $rootBusinessUnit;
    }

    protected function getExpectedData()
    {
        return [
        [
            'id' => 1,
            'name' => 'Main Business Unit (Organization 1)',
            'owner_id' => null
        ],
            [
                'id' => 2,
                'name' => 'Business Unit 1',
                'owner_id' => 1
            ]
        ];
    }
}
