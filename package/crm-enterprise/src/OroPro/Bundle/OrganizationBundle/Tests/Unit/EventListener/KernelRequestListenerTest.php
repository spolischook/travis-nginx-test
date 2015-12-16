<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

use OroPro\Bundle\OrganizationBundle\EventListener\KernelRequestListener;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\EntityWithOrganization;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class KernelRequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var KernelRequestListener */
    protected $listener;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $routingHelper;

    /** @var GlobalOrganization */
    protected $currentOrganization;

    public function setUp()
    {
        $this->organizationProvider = new SystemAccessModeOrganizationProvider();
        $this->doctrine             = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade       = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router               = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager      = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider     = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->routingHelper        = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->currentOrganization = new GlobalOrganization();
        $this->currentOrganization->setId(12);

        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->currentOrganization);

        $this->listener = new KernelRequestListener(
            $this->organizationProvider,
            $this->doctrine,
            $this->securityFacade,
            $this->router,
            $this->workflowManager,
            $this->metadataProvider,
            $this->routingHelper
        );
    }

    /**
     * @dataProvider kernelRequestSetOrganizationProvider
     * @param $requestParameters
     */
    public function testOnKernelRequestSetOrganization($requestParameters)
    {
        $request = new Request();
        $request->attributes->add($requestParameters);

        $organization = new GlobalOrganization();
        $organization->setIsGlobal(false);

        $repo = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroOrganizationBundle:Organization')
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('find')
            ->willReturnCallback(
                function ($id) use ($organization) {
                    $organization->setId($id);
                    return $organization;
                }
            );

        $event = new GetResponseEvent(
            $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')->disableOriginalConstructor()->getMock(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onKernelRequest($event);
        $this->assertEquals($organization->getId(), $this->organizationProvider->getOrganizationId());
    }

    public function kernelRequestSetOrganizationProvider()
    {
        return [
            [['form' => ['_sa_org_id' => 2]]],
            [['oro_workflow_transition' => ['_sa_org_id' => 3]]],
            [['_sa_org_id' => 4]],
        ];
    }

    public function testOnKernelRequestWorkflow()
    {
        $request = new Request();
        $request->attributes->add(
            [
                '_route'       => 'oro_workflow_start_transition_form',
                'workflowName' => 'testWorkflow'
            ]
        );
        $request->query->add(['entityId' => 0]);

        $entity = new EntityWithOrganization();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $definition->expects($this->once())
            ->method('getRelatedEntity')
            ->willReturn($entity);

        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('testWorkflow')
            ->willReturn($workflow);

        $metaData = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metaData);

        $this->router->expects($this->once())
            ->method('generate')
            ->willReturn('http://localhost');

        $event = new GetResponseEvent(
            $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')->disableOriginalConstructor()->getMock(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
    }

    public function testOnKernelRequestEntityUpdate()
    {
        $request = new Request();
        $request->attributes->add(
            [
                '_route'     => 'oro_entity_update',
                'entityName' => 'Oro\TestBundle\TestEntity',
                'id'         => 123
            ]
        );

        $this->routingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->willReturnCallback(
                function ($className) {
                    return $className;
                }
            );

        $organization = new GlobalOrganization();
        $organization->setId(54);

        $repo = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('Oro\TestBundle\TestEntity')
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('find')
            ->willReturnCallback(
                function ($id) use ($organization) {
                    $entity = new EntityWithOrganization();
                    $entity->setId($id);
                    $entity->setOrganization($organization);
                    return $entity;
                }
            );

        $metaData = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metaData);

        $event = new GetResponseEvent(
            $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')->disableOriginalConstructor()->getMock(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->listener->onKernelRequest($event);
        $this->assertSame($organization, $this->organizationProvider->getOrganization());
    }
}
