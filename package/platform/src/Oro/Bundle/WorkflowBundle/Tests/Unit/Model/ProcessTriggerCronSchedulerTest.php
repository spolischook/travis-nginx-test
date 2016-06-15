<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler;
use Oro\Component\Testing\Unit\EntityTrait;

class ProcessTriggerCronSchedulerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /** @var ScheduleManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $scheduleManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var string */
    protected $scheduleClass;

    /** @var ProcessTriggerCronScheduler */
    protected $processCronScheduler;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    protected function setUp()
    {
        $this->scheduleManager = $this->getMockBuilder('Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->scheduleClass = 'Oro\Bundle\CronBundle\Entity\Schedule';
        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->processCronScheduler = new ProcessTriggerCronScheduler(
            $this->scheduleManager,
            $this->registry,
            $this->scheduleClass
        );
    }

    public function testAddAndFlush()
    {
        /** @var ProcessTrigger|\PHPUnit_Framework_MockObject_MockObject $trigger * */
        $trigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');

        $cronExpression = '* * * * *';
        $trigger->expects($this->any())
            ->method('getCron')
            ->willReturn($cronExpression);

        //create arguments
        $processDefinitionMock = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')->getMock();
        $trigger->expects($this->once())
            ->method('getDefinition')
            ->willReturn($processDefinitionMock);
        $processDefinitionMock->expects($this->once())
            ->method('getName')
            ->willReturn('process-definition-name');
        $trigger->expects($this->once())
            ->method('getId')
            ->willReturn(100500);

        $arguments = ['--name=process-definition-name', '--id=100500'];

        //hasSchedule
        $this->scheduleManager->expects($this->once())
            ->method('hasSchedule')
            ->with(HandleProcessTriggerCommand::NAME, $arguments, $cronExpression)
            ->willReturn(false);

        //create schedule
        $scheduleEntity = new Schedule();
        $this->scheduleManager->expects($this->once())
            ->method('createSchedule')
            ->with(HandleProcessTriggerCommand::NAME, $arguments, $cronExpression)
            ->willReturn($scheduleEntity);

        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($this->objectManager);
        $this->objectManager->expects($this->once())->method('persist')->with($scheduleEntity);

        $this->processCronScheduler->add($trigger);

        $this->objectManager->expects($this->once())->method('flush');
        $this->processCronScheduler->flush();
        // second flush should be empty
        $this->processCronScheduler->flush();
    }

    public function testRemoveScheduleAndFlush()
    {
        /** @var ProcessTrigger|\PHPUnit_Framework_MockObject_MockObject $mockTrigger */
        $mockTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $mockProcessDefinition = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition');
        $mockProcessDefinition->expects($this->once())->method('getName')->willReturn('process_name');

        $mockTrigger->expects($this->exactly(2))->method('getCron')->willReturn('* * * * *');
        $mockTrigger->expects($this->exactly(1))->method('getId')->willReturn(42);
        $mockTrigger->expects($this->once())->method('getDefinition')->willReturn($mockProcessDefinition);

        $foundMatchedSchedule = (new Schedule())->setArguments(['--name=process_name', '--id=42']);
        $foundNonMatchedSchedule = (new Schedule())->setArguments(['--name=process_name', '--id=41']);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['command' => HandleProcessTriggerCommand::NAME, 'definition' => '* * * * *'])
            ->willReturn([$foundMatchedSchedule, $foundNonMatchedSchedule]);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with($this->scheduleClass)
            ->willReturn($repository);
        $this->objectManager->expects($this->once())
            ->method('contains')
            ->with($foundMatchedSchedule)
            ->willReturn(true);

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->objectManager);
        $this->objectManager->expects($this->once())->method('remove')->with($foundMatchedSchedule);

        $this->processCronScheduler->removeSchedule($mockTrigger);

        $this->processCronScheduler->flush();
        // second flush should be empty
        $this->processCronScheduler->flush();
    }

    public function testException()
    {
        /** @var ProcessTrigger|\PHPUnit_Framework_MockObject_MockObject $mockTrigger */
        $mockTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $mockTrigger->expects($this->exactly(1))->method('getCron')->willReturn(null);
        $this->setExpectedException(
            'InvalidArgumentException',
            'Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler supports only cron schedule triggers.'
        );

        $this->processCronScheduler->removeSchedule($mockTrigger);
    }

    public function testAddException()
    {
        /** @var ProcessTrigger|\PHPUnit_Framework_MockObject_MockObject $trigger * */
        $trigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $trigger->expects($this->once())->method('getCron')->willReturn(null);

        $this->setExpectedException('InvalidArgumentException');
        $this->processCronScheduler->add($trigger);
    }

    public function testUnmanageableEntityException()
    {
        $this->setValue($this->processCronScheduler, 'dirty', true);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->scheduleClass)
            ->willReturn(null);

        $this->setExpectedException('InvalidArgumentException', 'Please provide manageable schedule entity class');

        $this->processCronScheduler->flush();
    }
}
