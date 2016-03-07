<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Model\Action\ChangeCategoryVisibility;

class ChangeCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChangeCategoryVisibility
     */
    protected $action;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var  CategoryCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action = new ChangeCategoryVisibility($contextAccessor);

        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->cacheBuilder = $this
            ->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action->setDispatcher($dispatcher);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
    }

    protected function tearDown()
    {
        unset($this->action);
    }

    /**
     * @dataProvider executeActionDataProvider
     * @param bool $throwException
     */
    public function testExecuteAction($throwException = false)
    {
        $categoryVisibility = new CategoryVisibility();

        /** @var CategoryCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $cacheBuilder */
        $cacheBuilder = $this
            ->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');

        /** @var Registry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $cacheBuilder->expects($this->once())
                ->method('resolveVisibilitySettings')
                ->with($categoryVisibility)
                ->will($this->throwException(new \Exception('Error')));

            $em->expects($this->once())
                ->method('rollback');

            $this->setExpectedException('\Exception', 'Error');
        } else {
            $cacheBuilder->expects($this->once())
                ->method('resolveVisibilitySettings')
                ->with($categoryVisibility);

            $em->expects($this->once())
                ->method('commit');
        }

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($em);

        $this->action->setCacheBuilder($cacheBuilder);
        $this->action->setRegistry($registry);
        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $categoryVisibility]));
    }

    /**
     * @return array
     */
    public function executeActionDataProvider()
    {
        return [
            [
                'throwException' => true
            ],
            [
                'throwException' => false
            ],
        ];
    }
}
