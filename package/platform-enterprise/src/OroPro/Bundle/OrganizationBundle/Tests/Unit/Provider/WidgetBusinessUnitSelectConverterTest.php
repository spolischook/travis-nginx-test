<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use OroPro\Bundle\OrganizationBundle\Provider\WidgetBusinessUnitSelectConverter;

class WidgetBusinessUnitSelectConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetBusinessUnitSelectConverter */
    protected $converter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $businessAclProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organization;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $expr;

    /** @var array */
    protected $buIds = [1, 2, 3];

    /** @var array */
    protected $config = ['aclClass' => 'test', 'aclPermission' => 'VIEW'];

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['createQueryBuilder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->businessAclProvider = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Provider\BusinessUnitAclProvider')
            ->setMethods(['getBusinessUnitIds', 'getProcessedEntityAccessLevel'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getQuery', 'getResult', 'andWhere', 'expr'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity')
            ->setMethods(['getIsGlobal'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue([]));

        $this->entityRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $this->businessAclProvider->expects($this->any())
            ->method('getBusinessUnitIds')
            ->will($this->returnValue($this->buIds));

        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($this->organization));

        $this->converter = new WidgetBusinessUnitSelectConverter(
            $this->entityRepository,
            $this->securityFacade,
            $this->businessAclProvider
        );
    }

    public function testAclDidntApplied()
    {
        $this->businessAclProvider
            ->expects($this->exactly(0))
            ->method('getBusinessUnitIds');

        $this->converter->getBusinessUnitList([]);
    }

    public function testLessSystemLevel()
    {
        $this->businessAclProvider
            ->expects($this->exactly(1))
            ->method('getBusinessUnitIds')
            ->with($this->config['aclClass'], $this->config['aclPermission']);

        $this->businessAclProvider
            ->expects($this->exactly(1))
            ->method('getProcessedEntityAccessLevel')
            ->will($this->returnValue(AccessLevel::DEEP_LEVEL));

        $this->organization
            ->expects($this->exactly(1))
            ->method('getIsGlobal')
            ->will($this->returnValue(true));

        $this->queryBuilder
            ->expects($this->exactly(1))
            ->method('expr')
            ->will($this->returnValue($this->expr));

        $this->expr
            ->expects($this->exactly(1))
            ->method('eq')
            ->with('businessUnit.id', 0);

        $this->converter->getBusinessUnitList($this->config);
    }

    public function testSystemLevel()
    {
        $this->businessAclProvider
            ->expects($this->exactly(1))
            ->method('getBusinessUnitIds')
            ->with($this->config['aclClass'], $this->config['aclPermission']);

        $this->businessAclProvider
            ->expects($this->exactly(1))
            ->method('getProcessedEntityAccessLevel')
            ->will($this->returnValue(AccessLevel::SYSTEM_LEVEL));

        $this->organization
            ->expects($this->exactly(1))
            ->method('getIsGlobal')
            ->will($this->returnValue(true));

        $this->queryBuilder
            ->expects($this->exactly(1))
            ->method('expr')
            ->will($this->returnValue($this->expr));

        $this->expr
            ->expects($this->exactly(0))
            ->method('eq')
            ->with('businessUnit.id', 0);

        $this->converter->getBusinessUnitList($this->config);
    }

    public function testNonGlobalOrganization()
    {
        $this->businessAclProvider
            ->expects($this->exactly(1))
            ->method('getBusinessUnitIds')
            ->with($this->config['aclClass'], $this->config['aclPermission']);

        $this->organization
            ->expects($this->exactly(1))
            ->method('getIsGlobal')
            ->will($this->returnValue(false));

        $this->queryBuilder
            ->expects($this->exactly(1))
            ->method('expr')
            ->will($this->returnValue($this->expr));

        $this->expr
            ->expects($this->exactly(1))
            ->method('in')
            ->with('businessUnit.id', $this->buIds);

        $this->converter->getBusinessUnitList($this->config);
    }
}
