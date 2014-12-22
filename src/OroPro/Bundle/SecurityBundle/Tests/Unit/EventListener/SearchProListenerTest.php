<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Query;

use OroPro\Bundle\SecurityBundle\EventListener\SearchProListener;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class SearchProListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SearchProListener */
    protected $listener;

    /**  @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organizationProvider;

    /** @var Query */
    protected $query;

    public function setUp()
    {
        $this->metadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new SearchProListener($this->metadataProvider, $this->securityFacade);

        $this->organizationProvider = $this
            ->getMockBuilder('OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setOrganizationProvider($this->organizationProvider);

        $this->query = new Query();
        $this->query->from('testEntity')->andWhere('someTextField', '~', 'test');
    }

    public function testBeforeSearchEventRegularMode()
    {
        $organization = new GlobalOrganization();
        $organization->setIsGlobal(false);
        $this->securityFacade->expects($this->once())->method('getOrganization')->willReturn($organization);

        $this->securityFacade->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue(10));
        $event = new BeforeSearchEvent($this->query);

        $this->listener->beforeSearchEvent($event);

        $wherePart = $this->query->getOptions();
        $this->assertCount(2, $wherePart);
        $expexted = [
            'fieldName' => 'organization',
            'condition' => 'in',
            'fieldValue' => [10, 0],
            'fieldType' => 'integer',
            'type' => 'and'
        ];
        $this->assertEquals($expexted, $wherePart[1]);
    }

    public function testBeforeSearchEventSystemMode()
    {
        $organization = new GlobalOrganization();
        $this->securityFacade->expects($this->once())->method('getOrganization')->willReturn($organization);


        $this->securityFacade->expects($this->never())
            ->method('getOrganizationId');
        $event = new BeforeSearchEvent($this->query);

        $this->listener->beforeSearchEvent($event);

        $wherePart = $this->query->getOptions();
        $this->assertCount(1, $wherePart);
    }

    public function testBeforeSearchEventSystemModeWithAdditionalOrg()
    {
        $organization = new GlobalOrganization();
        $this->securityFacade->expects($this->once())->method('getOrganization')->willReturn($organization);

        $this->organizationProvider->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(2);

        $this->securityFacade->expects($this->never())
            ->method('getOrganizationId');
        $event = new BeforeSearchEvent($this->query);

        $this->listener->beforeSearchEvent($event);

        $wherePart = $this->query->getOptions();
        $this->assertCount(2, $wherePart);
        $this->assertEquals(
            [
                'fieldName' => 'organization',
                'condition' => 'in',
                'fieldValue' => [2, 0],
                'fieldType' => 'integer',
                'type' => 'and'
            ],
            $wherePart[1]
        );
    }
}
