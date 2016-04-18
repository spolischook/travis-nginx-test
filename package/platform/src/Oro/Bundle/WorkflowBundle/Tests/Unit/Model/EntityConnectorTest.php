<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityWithWorkflow;

class EntityConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    protected function setUp()
    {
        $this->entityConnector = new EntityConnector();
    }

    protected function tearDown()
    {
        unset($this->entityConnector);
    }

    public function testResetWorkflowData()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($entity->getWorkflowItem());

        $workflowItem = new WorkflowItem();
        $this->entityConnector->setWorkflowItem($entity, $workflowItem);

        $workflowStep = new WorkflowStep();
        $this->entityConnector->setWorkflowStep($entity, $workflowStep);

        $this->assertEquals($workflowItem, $entity->getWorkflowItem());
        $this->assertEquals($workflowStep, $entity->getWorkflowStep());

        $this->entityConnector->resetWorkflowData($entity);
        $this->assertNull($entity->getWorkflowItem());
        $this->assertNull($entity->getWorkflowStep());
    }

    public function testSetWorkflowItem()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($entity->getWorkflowItem());

        $workflowItem = new WorkflowItem();
        $this->entityConnector->setWorkflowItem($entity, $workflowItem);
        $this->assertEquals($workflowItem, $entity->getWorkflowItem());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't set property "workflowItem" to entity
     */
    public function testSetWorkflowItemException()
    {
        $this->entityConnector->setWorkflowItem(new \DateTime(), new WorkflowItem());
    }

    public function testSetWorkflowStep()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($entity->getWorkflowItem());

        $workflowStep = new WorkflowStep();
        $this->entityConnector->setWorkflowStep($entity, $workflowStep);
        $this->assertEquals($workflowStep, $entity->getWorkflowStep());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't set property "workflowStep" to entity
     */
    public function testSetWorkflowStepException()
    {
        $this->entityConnector->setWorkflowStep(new \DateTime(), new WorkflowStep());
    }

    public function testGetWorkflowItem()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($this->entityConnector->getWorkflowItem($entity));

        $workflowItem = new WorkflowItem();
        $entity->setWorkflowItem($workflowItem);
        $this->assertEquals($workflowItem, $this->entityConnector->getWorkflowItem($entity));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't get property "workflowItem" from entity
     */
    public function testGetWorkflowItemException()
    {
        $this->entityConnector->getWorkflowItem(new \DateTime());
    }

    public function testGetWorkflowStep()
    {
        $entity = new EntityWithWorkflow();
        $this->assertEmpty($this->entityConnector->getWorkflowStep($entity));

        $workflowStep = new WorkflowStep();
        $entity->setWorkflowStep($workflowStep);
        $this->assertEquals($workflowStep, $this->entityConnector->getWorkflowStep($entity));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't get property "workflowStep" from entity
     */
    public function testGetWorkflowStepException()
    {
        $this->entityConnector->getWorkflowStep(new \DateTime());
    }

    /**
     * @dataProvider workflowAwareDataProvider
     * @param object $entity
     * @param bool $expected
     */
    public function testIsWorkflowItemAware($entity, $expected)
    {
        $this->assertEquals($expected, $this->entityConnector->isWorkflowAware($entity));
    }

    public function testInterfaceInteraction()
    {
        $entity = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface');
        $workflowStep = new WorkflowStep();
        $workflowItem = new WorkflowItem();

        $entity->expects($this->once())
            ->method('setWorkflowStep')
            ->with($workflowStep);
        $entity->expects($this->once())
            ->method('setWorkflowItem')
            ->with($workflowItem);
        $entity->expects($this->once())
            ->method('getWorkflowStep')
            ->will($this->returnValue($workflowStep));
        $entity->expects($this->once())
            ->method('getWorkflowItem')
            ->will($this->returnValue($workflowItem));

        $this->entityConnector->setWorkflowStep($entity, $workflowStep);
        $this->entityConnector->setWorkflowItem($entity, $workflowItem);

        $this->assertSame($workflowItem, $this->entityConnector->getWorkflowItem($entity));
        $this->assertSame($workflowStep, $this->entityConnector->getWorkflowStep($entity));
    }

    /**
     * @return array
     */
    public function workflowAwareDataProvider()
    {
        return [
            [new EntityWithWorkflow(), true],
            [new \DateTime(), false],
            [$this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface'), true]
        ];
    }
}
