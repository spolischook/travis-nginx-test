<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\EventListener;

use OroPro\Bundle\EwsBundle\EventListener\EmailOriginListener;

use Oro\Bundle\UserBundle\Entity\User;

class EmailOriginListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailOriginListener
     */
    protected $listener;

    /**
     * @var array
     */
    protected $changeSet = [
        'email' => ['change1@email.com']
    ];

    /**
     * @var array
     */
    protected $entities;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $email;

    /**
     * @var string
     */
    protected $deletedEmail = 'deleted1@email.com';

    public function setUp()
    {
        $this->user = new User();
        $this->email = $this
            ->getMockBuilder('Oro\Bundle\UserBundle\Entity\Email')
            ->setMethods(['getEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->email->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue($this->deletedEmail));

        $this->entities = [$this->user, $this->email];
        $this->listener = new EmailOriginListener();
    }

    public function testOnFlushOriginDeactivator()
    {
        $event = $this
            ->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $unitOfWork = $this
            ->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->setMethods(['getScheduledEntityUpdates', 'getScheduledEntityDeletions', 'getEntityChangeSet'])
            ->disableOriginalConstructor()
            ->getMock();

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($this->entities));

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue($this->entities));

        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($this->changeSet));

        $methods = [
            'createQueryBuilder',
            'getUnitOfWork',
            'update',
            'set',
            'where',
            'getQuery',
            'execute',
            'expr',
            'literal',
            'in'
        ];

        $values = [
            'getUnitOfWork' => $unitOfWork
        ];

        $entityManager = $this->prepareEntityManagerMethods($methods, $values);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $this->assertCount(3, $this->listener->onFlush($event));
    }

    /**
     * @param array $methods
     * @param array $values
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareEntityManagerMethods($methods, $values)
    {
        $entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($methods as $method) {
            if (array_key_exists($method, $values)) {
                $value = $values[$method];
            } else {
                $value = $entityManager;
            }

            $entityManager->expects($this->any())->method($method)->will($this->returnValue($value));
        }

        return $entityManager;
    }
}
