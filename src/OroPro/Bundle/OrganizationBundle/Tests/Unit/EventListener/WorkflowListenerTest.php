<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Component\Action\Event\ExecuteActionEvent;

use OroPro\Bundle\OrganizationBundle\EventListener\WorkflowListener;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\EntityWithOrganization;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class WorkflowListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowListener */
    protected $listener;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    public function setUp()
    {
        $this->organizationProvider = new SystemAccessModeOrganizationProvider();
        $this->securityFacade       = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper       = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->metadataProvider     = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowListener(
            $this->organizationProvider,
            $this->securityFacade,
            $this->doctrineHelper,
            $this->metadataProvider
        );
    }

    public function testOnBeforeAction()
    {
        $currentOrg = new GlobalOrganization();
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($currentOrg);
        $selectedOrg = new GlobalOrganization();
        $selectedOrg->setId(23);
        $this->organizationProvider->setOrganization($selectedOrg);

        $action = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity')
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new EntityWithOrganization();

        $context = new WorkflowItem();
        $context->setEntity($testEntity);

        $metadata = new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $event = new ExecuteActionEvent($context, $action);

        $this->listener->onBeforeAction($event);

        $this->assertSame($selectedOrg, $testEntity->getOrganization());
    }

    public function testOnStartTransition()
    {
        $workflow   = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $transition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $event      = new StartTransitionEvent($workflow, $transition, []);

        $currentOrg  = new GlobalOrganization();
        $selectedOrg = new GlobalOrganization();
        $selectedOrg->setId(23);
        $this->organizationProvider->setOrganization($selectedOrg);
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($currentOrg);

        $this->listener->onStartTransition($event);

        $this->assertEquals(['_sa_org_id' => 23], $event->getRouteParameters());
    }
}
