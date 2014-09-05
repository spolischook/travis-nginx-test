<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\SearchBundle\Command\IndexCommand;
use OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;
use OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub\TestEntity;

class ElasticSearchTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'Stub\TestEntity';
    const TEST_ALIAS = 'test_entity';
    const TEST_INDEX = 'test_index';

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

        $this->mapper->expects($this->any())->method('mapObject')
            ->with($this->isInstanceOf('OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub\TestEntity'))
            ->will(
                $this->returnCallback(
                    function (TestEntity $entity) {
                        $map = array('text' => array());
                        if ($entity->name) {
                            $map['text']['name'] = $entity->name;
                        }
                        if ($entity->birthday) {
                            $map['datetime']['birthday'] = $entity->birthday;
                        }
                        if ($entity->entity) {
                            $map['text']['entity'] = $entity->entity;
                        }
                        return $map;
                    }
                )
            );
        $this->mapper->expects($this->any())->method('getEntitiesListAliases')
            ->will($this->returnValue(array(self::TEST_CLASS => self::TEST_ALIAS)));

        $this->indexAgent->expects($this->any())->method('getIndexName')
            ->will($this->returnValue(self::TEST_INDEX));

        $this->engine = new ElasticSearch(
            $this->registry,
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mapper,
            $this->indexAgent
        );
    }

    /**
     * @param object|array $entity
     * @param array $jobArguments
     * @param bool $result
     * @param bool $isSave
     * @dataProvider queuedOperationDataProvider
     */
    public function testQueuedOperation($entity, array $jobArguments, $result, $isSave)
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        if ($entity) {
            $entityManager->expects($this->once())->method('persist')
                ->with($this->isInstanceOf('JMS\JobQueueBundle\Entity\Job'))
                ->will(
                    $this->returnCallback(
                        function (Job $job) use ($jobArguments) {
                            $this->assertEquals($job->getCommand(), IndexCommand::NAME);
                            $this->assertEquals($jobArguments, $job->getArgs());
                        }
                    )
                );
            $entityManager->expects($this->once())->method('flush');
        } else {
            $entityManager->expects($this->never())->method('persist');
            $entityManager->expects($this->never())->method('flush');
        }

        $this->registry->expects($this->any())->method('getManagerForClass')->with('JMSJobQueueBundle:Job')
            ->will($this->returnValue($entityManager));

        if ($isSave) {
            $this->assertEquals($result, $this->engine->save($entity, false));
        } else {
            $this->assertEquals($result, $this->engine->delete($entity, false));
        }
    }

    /**
     * @return array
     */
    public function queuedOperationDataProvider()
    {
        return array(
            'save with entities' => array(
                'entity' => array(new TestEntity(1), new TestEntity(2)),
                'jobArguments' => array(self::TEST_CLASS, 1, 2),
                'result' => true,
                'isSave' => true
            ),
            'save without entities' => array(
                'entity' => array(),
                'jobArguments' => array(),
                'result' => false,
                'isSave' => true
            ),
            'delete with entities' => array(
                'entity' => new TestEntity(1),
                'jobArguments' => array(self::TEST_CLASS, 1),
                'result' => true,
                'isSave' => false
            ),
            'delete without entities' => array(
                'entity' => null,
                'jobArguments' => array(),
                'result' => false,
                'isSave' => false
            ),
        );
    }

    /**
     * @param object|array $entity
     * @param array $body
     * @param array $response
     * @param bool $result
     * @param bool $isSave
     * @dataProvider realTimeOperationDataProvider
     */
    public function testRealTimeOperation($entity, array $body, array $response, $result, $isSave)
    {
        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        if ($body) {
            $client->expects($this->once())->method('bulk')->with(array('index' => self::TEST_INDEX, 'body' => $body))
                ->will($this->returnValue($response));
        } else {
            $client->expects($this->never())->method('bulk');
        }

        $this->indexAgent->expects($this->any())->method('initializeClient')
            ->will($this->returnValue($client));

        if ($isSave) {
            $this->assertEquals($result, $this->engine->save($entity));
        } else {
            $this->assertEquals($result, $this->engine->delete($entity));
        }
    }

    /**
     * @return array
     */
    public function realTimeOperationDataProvider()
    {
        $utcDate = new \DateTime('2012-12-12 12:12:12', new \DateTimeZone('UTC'));
        $notUtcDate = new \DateTime('2012-12-12 14:12:12', new \DateTimeZone('Europe/Athens'));
        $expectedDate = '2012-12-12 12:12:12';

        return array(
            'save successful' => array(
                'entity' => array(
                    new TestEntity(1, 'name1', $utcDate),
                    new TestEntity(2, 'name2', $notUtcDate),
                    new TestEntity(3, 'name3', null, new TestEntity(null, 'entity3')),
                ),
                'body' => array(
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 1)),
                    array('create' => array('_type' => self::TEST_ALIAS, '_id' => 1)),
                    array('name' => 'name1', 'birthday' => $expectedDate),
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 2)),
                    array('create' => array('_type' => self::TEST_ALIAS, '_id' => 2)),
                    array('name' => 'name2', 'birthday' => $expectedDate),
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 3)),
                    array('create' => array('_type' => self::TEST_ALIAS, '_id' => 3)),
                    array('name' => 'name3', 'entity' => 'entity3'),
                ),
                'response' => array('errors' => false),
                'result' => true,
                'isSave' => true
            ),
            'save not successful' => array(
                'entity' => array(
                    new TestEntity(1, 'name1'),
                    new TestEntity(2)
                ),
                'body' => array(
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 1)),
                    array('create' => array('_type' => self::TEST_ALIAS, '_id' => 1)),
                    array('name' => 'name1'),
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 2)),
                ),
                'response' => array('errors' => true),
                'result' => false,
                'isSave' => true
            ),
            'save without body' => array(
                'entity' => array(new TestEntity()),
                'body' => array(),
                'response' => array(),
                'result' => false,
                'isSave' => true
            ),
            'delete successful' => array(
                'entity' => array(
                    new TestEntity(1, 'firstName1', 'lastName1'),
                    new TestEntity(2, 'firstName2')
                ),
                'body' => array(
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 1)),
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 2)),
                ),
                'response' => array('errors' => false),
                'result' => true,
                'isSave' => false
            ),
            'delete not successful' => array(
                'entity' => new TestEntity(1, 'firstName1', 'lastName1'),
                'body' => array(
                    array('delete' => array('_type' => self::TEST_ALIAS, '_id' => 1)),
                ),
                'response' => array('errors' => true),
                'result' => false,
                'isSave' => false
            ),
            'delete without body' => array(
                'entity' => array(new TestEntity()),
                'body' => array(),
                'response' => array(),
                'result' => false,
                'isSave' => false
            ),
        );
    }

    public function testReindexAll()
    {
        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $entities = array('firstEntity', 'secondEntity');

        $this->indexAgent->expects($this->once())->method('recreateIndex')
            ->will($this->returnValue($client));

        $this->mapper->expects($this->any())->method('getEntities')
            ->will($this->returnValue($entities));

        $engine = $this->getEngineMock();
        $engine->expects($this->at(0))->method('reindexSingleEntity')->with('firstEntity')
            ->will($this->returnValue(1));
        $engine->expects($this->at(1))->method('reindexSingleEntity')->with('secondEntity')
            ->will($this->returnValue(2));

        $this->assertEquals(3, $engine->reindex());
        $this->assertAttributeEquals($client, 'client', $engine);
    }

    public function testReindexOneEntity()
    {
        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexAgent->expects($this->once())->method('initializeClient')
            ->will($this->returnValue($client));
        $this->indexAgent->expects($this->once())->method('recreateTypeMapping')->with($client, self::TEST_CLASS)
            ->will($this->returnValue($client));

        $count = 123;

        $engine = $this->getEngineMock();
        $engine->expects($this->once())->method('reindexSingleEntity')->with(self::TEST_CLASS)
            ->will($this->returnValue($count));

        $this->assertEquals($count, $engine->reindex(self::TEST_CLASS));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ElasticSearch
     */
    protected function getEngineMock()
    {
        $arguments = array(
            $this->registry,
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mapper,
            $this->indexAgent
        );

        return $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch')
            ->setConstructorArgs($arguments)
            ->setMethods(array('reindexSingleEntity'))
            ->getMock();
    }
}
