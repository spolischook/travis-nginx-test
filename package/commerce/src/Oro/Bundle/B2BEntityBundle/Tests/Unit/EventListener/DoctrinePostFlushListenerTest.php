<?php

namespace Oro\Bundle\B2BEntityBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\B2BEntityBundle\EventListener\DoctrinePostFlushListener;
use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorage;
use Oro\Bundle\B2BEntityBundle\Tests\Stub\ObjectIdentifierAware;
use Oro\Bundle\B2BEntityBundle\Tests\Stub\Entity1;
use Oro\Bundle\B2BEntityBundle\Tests\Stub\Entity2;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DoctrinePostFlushListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostFlush()
    {
        $registry = $this->getRegistryMock();

        $testEntity1 = new Entity1();
        $testEntity2 = new Entity1();
        $testEntity3 = new ObjectIdentifierAware(2, 2);
        $testEntity4 = new Entity2();

        $storage = $this->getStorage();
        $storage->scheduleForExtraInsert($testEntity1);
        $storage->scheduleForExtraInsert($testEntity2);
        $storage->scheduleForExtraInsert($testEntity3);
        $storage->scheduleForExtraInsert($testEntity4);

        $em1 = $this->getEntityManagerMock();
        $em2 = $this->getEntityManagerMock();

        //method 'persist' should be called once for every entity
        $em1->expects($this->at(0))
            ->method('persist')
            ->with($testEntity1);
        $em1->expects($this->at(1))
            ->method('persist')
            ->with($testEntity2);

        $em2->expects($this->at(0))
            ->method('persist')
            ->with($testEntity3);
        $em2->expects($this->at(1))
            ->method('persist')
            ->with($testEntity4);

        //method 'flush' should be called only once for every manager
        $em1->expects($this->once())
            ->method('flush');
        $em2->expects($this->once())
            ->method('flush');

        //method 'getManagerForClass' should be called only once for every entity class
        $registry->expects($this->at(0))
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($testEntity1))
            ->willReturn($em1);

        $registry->expects($this->at(1))
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($testEntity3))
            ->willReturn($em2);

        $registry->expects($this->at(2))
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($testEntity4))
            ->willReturn($em2);


        $doctrineHelper = $this->getDoctrineHelper($registry);
        $listener = new DoctrinePostFlushListener($doctrineHelper, $storage);
        $listener->postFlush();
        $this->assertFalse($storage->hasScheduledForInsert());
    }

    public function testPostFlushDisabled()
    {
        $testEntity = new Entity1();
        $storage = $this->getStorage();
        $storage->scheduleForExtraInsert($testEntity);

        $listener = new DoctrinePostFlushListener($this->getDoctrineHelper(), $storage);
        $listener->setEnabled(false);
        $listener->postFlush();
        $this->assertTrue($storage->hasScheduledForInsert());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|Registry $registry
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper(Registry $registry = null)
    {
        $registry = $registry ?: $this->getRegistryMock();
        return new DoctrineHelper($registry);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected function getRegistryMock()
    {
        return $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ExtraActionEntityStorage
     */
    protected function getStorage()
    {
        return new ExtraActionEntityStorage();
    }
}
