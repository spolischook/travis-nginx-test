<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Extension;

use OroPro\Bundle\OrganizationBundle\Form\Extension\OwnerProFormExtension;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class OwnerProFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var OwnerProFormExtension */
    protected $formExtension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityOwnerAccessor;

    public function setUp()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $bUnitManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();
        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityOwnerAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityOwnerAccessor->expects($this->any())
            ->method('getOwner')
            ->willReturnCallback(
                function ($entity) {
                    return $entity->getOwner();
                }
            );

        $this->formExtension = new OwnerProFormExtension(
            $doctrineHelper,
            $metadataProvider,
            $bUnitManager,
            $this->securityFacade,
            $aclVoter,
            $treeProvider,
            $this->entityOwnerAccessor
        );

        $this->organizationProvider = new SystemAccessModeOrganizationProvider();
        $this->formExtension->setOrganizationProvider($this->organizationProvider);
    }

    public function testGetOrganizationId()
    {
        $reflection = new \ReflectionObject($this->formExtension);
        $method = $reflection->getMethod('getOrganization');

        $method->setAccessible(true);

        $currentOrganization = new GlobalOrganization();
        $currentOrganization->setId(8);

        $selectedOrganization = new GlobalOrganization();
        $selectedOrganization->setId(5);

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($currentOrganization);
        $this->organizationProvider->setOrganization($selectedOrganization);

        $this->assertSame($selectedOrganization, $method->invoke($this->formExtension));
    }
}
