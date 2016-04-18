<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Twig;

use Oro\Bundle\ReminderBundle\Twig\ReminderExtension;

class ReminderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReminderExtension
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paramsProvider;

    protected function setUp()
    {
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paramsProvider  = $this->getMockBuilder(
            'Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ReminderExtension($this->entityManager, $this->securityContext, $this->paramsProvider);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotExist()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMockForAbstractClass();
        $token->expects($this->once())->method('getUser')->will($this->returnValue(null));
        $this->securityContext->expects($this->atLeastOnce())->method('getToken')->will($this->returnValue($token));

        $actual = $this->target->getRequestedRemindersData();
        $this->assertEquals(array(), $actual);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotEqualType()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMockForAbstractClass();
        $token->expects($this->once())->method('getUser')->will($this->returnValue(new \stdClass()));
        $this->securityContext->expects($this->atLeastOnce())->method('getToken')->will($this->returnValue($token));

        $actual = $this->target->getRequestedRemindersData();
        $this->assertEquals(array(), $actual);
    }

    public function testGetRequestedRemindersReturnCorrectData()
    {
        $reminder  = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder1 = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder2 = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');

        $expectedReminder      = new \stdClass();
        $expectedReminder->id  = 42;
        $expectedReminder1     = new \stdClass();
        $expectedReminder1->id = 12;
        $expectedReminder2     = new \stdClass();
        $expectedReminder2->id = 22;
        $expectedReminders     = array($expectedReminder, $expectedReminder1, $expectedReminder2);

        $reminders = array($reminder, $reminder1, $reminder2);
        $token     = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMockForAbstractClass();
        $user      = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock(
        );
        $token->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $repository = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findRequestedReminders')
            ->with($this->equalTo($user))
            ->will($this->returnValue($reminders));
        $this->entityManager->expects($this->once())
            ->method('getRepository')->will($this->returnValue($repository));
        $this->securityContext->expects($this->atLeastOnce())->method('getToken')->will($this->returnValue($token));

        $this->paramsProvider->expects($this->once())
            ->method('getMessageParamsForReminders')
            ->with($reminders)
            ->will($this->returnValue($expectedReminders));

        $actual = $this->target->getRequestedRemindersData();
        $this->assertEquals($expectedReminders, $actual);
    }
}
