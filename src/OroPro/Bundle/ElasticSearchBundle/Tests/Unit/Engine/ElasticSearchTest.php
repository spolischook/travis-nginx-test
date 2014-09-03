<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\SearchBundle\Command\IndexCommand;
use OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;
use OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub\TestEntity;

class ElasticSearchTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'Stub\TestEntity';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexAgent;

    /**
     * @var ElasticSearch
     */
    protected $engine;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapper = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\ObjectMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexAgent = $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Engine\IndexAgent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())->method('getEntityClass')
            ->with($this->isInstanceOf('OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub\TestEntity'))
            ->will($this->returnValue(self::TEST_CLASS));
        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')
            ->with($this->isInstanceOf('OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub\TestEntity'))
            ->will(
                $this->returnCallback(
                    function (TestEntity $entity) {
                        return $entity->id;
                    }
                )
            );

        $this->engine = new ElasticSearch(
            $this->registry,
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mapper,
            $this->indexAgent
        );
    }

    /**
     * @param bool $isSave
     * @dataProvider queuedOperationDataProvider
     */
    public function testQueuedOperation($isSave)
    {
        $entities = array(new TestEntity(1), new TestEntity(2));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf('JMS\JobQueueBundle\Entity\Job'))
            ->will(
                $this->returnCallback(
                    function (Job $job) {
                        $this->assertEquals($job->getCommand(), IndexCommand::NAME);
                        $this->assertEquals(array(self::TEST_CLASS, 1, 2), $job->getArgs());
                    }
                )
            );

        $this->registry->expects($this->any())->method('getManagerForClass')->with('JMSJobQueueBundle:Job')
            ->will($this->returnValue($entityManager));

        if ($isSave) {
            $this->engine->save($entities, false);
        } else {
            $this->engine->delete($entities, false);
        }
    }

    /**
     * @return array
     */
    public function queuedOperationDataProvider()
    {
        return array(
            'save' => array(true),
            'delete' => array(false),
        );
    }
}
