<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\SearchBundle\Command\IndexCommand;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;
use OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub\TestEntity;

class ElasticSearchTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'Stub\TestEntity';
    const TEST_DESCENDANT_1 = 'Stub\TestChildEntity1';
    const TEST_DESCENDANT_2 = 'Stub\TestChildEntity2';
    const TEST_ALIAS = 'test_entity';
    const TEST_INDEX = 'test_index';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ElasticSearch
     */
    protected $engine;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
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
                        $map = ['text' => []];
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
            ->will($this->returnValue([self::TEST_CLASS => self::TEST_ALIAS]));

        $this->indexAgent->expects($this->any())->method('getIndexName')
            ->will($this->returnValue(self::TEST_INDEX));

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

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
        return [
            'save with entities' => [
                'entity' => [new TestEntity(1), new TestEntity(2)],
                'jobArguments' => [self::TEST_CLASS, 1, 2],
                'result' => true,
                'isSave' => true
            ],
            'save without entities' => [
                'entity' => [],
                'jobArguments' => [],
                'result' => false,
                'isSave' => true
            ],
            'delete with entities' => [
                'entity' => new TestEntity(1),
                'jobArguments' => [self::TEST_CLASS, 1],
                'result' => true,
                'isSave' => false
            ],
            'delete without entities' => [
                'entity' => null,
                'jobArguments' => [],
                'result' => false,
                'isSave' => false
            ],
        ];
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
            $client->expects($this->once())->method('bulk')->with(['index' => self::TEST_INDEX, 'body' => $body])
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

        return [
            'save successful' => [
                'entity' => [
                    new TestEntity(1, 'name1', $utcDate),
                    new TestEntity(2, 'name2', $notUtcDate),
                    new TestEntity(3, 'name3', null, new TestEntity(null, 'entity3')),
                ],
                'body' => [
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 1]],
                    ['create' => ['_type' => self::TEST_ALIAS, '_id' => 1]],
                    ['name' => 'name1', 'birthday' => $expectedDate],
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 2]],
                    ['create' => ['_type' => self::TEST_ALIAS, '_id' => 2]],
                    ['name' => 'name2', 'birthday' => $expectedDate],
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 3]],
                    ['create' => ['_type' => self::TEST_ALIAS, '_id' => 3]],
                    ['name' => 'name3', 'entity' => 'entity3'],
                ],
                'response' => ['errors' => false],
                'result' => true,
                'isSave' => true
            ],
            'save not successful' => [
                'entity' => [
                    new TestEntity(1, 'name1'),
                    new TestEntity(2)
                ],
                'body' => [
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 1]],
                    ['create' => ['_type' => self::TEST_ALIAS, '_id' => 1]],
                    ['name' => 'name1'],
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 2]],
                ],
                'response' => ['errors' => true],
                'result' => false,
                'isSave' => true
            ],
            'save without body' => [
                'entity' => [new TestEntity()],
                'body' => [],
                'response' => [],
                'result' => false,
                'isSave' => true
            ],
            'delete successful' => [
                'entity' => [
                    new TestEntity(1, 'firstName1', 'lastName1'),
                    new TestEntity(2, 'firstName2')
                ],
                'body' => [
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 1]],
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 2]],
                ],
                'response' => ['errors' => false],
                'result' => true,
                'isSave' => false
            ],
            'delete not successful' => [
                'entity' => new TestEntity(1, 'firstName1', 'lastName1'),
                'body' => [
                    ['delete' => ['_type' => self::TEST_ALIAS, '_id' => 1]],
                ],
                'response' => ['errors' => true],
                'result' => false,
                'isSave' => false
            ],
            'delete without body' => [
                'entity' => [new TestEntity()],
                'body' => [],
                'response' => [],
                'result' => false,
                'isSave' => false
            ],
        ];
    }

    public function testReindexAll()
    {
        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $entities = ['firstEntity', 'secondEntity'];

        $this->indexAgent->expects($this->once())->method('recreateIndex')
            ->will($this->returnValue($client));

        $this->mapper->expects($this->any())->method('getEntities')->with([Mode::NORMAL, Mode::WITH_DESCENDANTS])
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
            ->disableOriginalConstructor()->getMock();

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
     * @dataProvider entityModeDataProvider
     *
     * @param string  $mode
     * @param array   $descendants
     * @param array   $expectedEntitiesToProcess
     */
    public function testReindexEntityWithMode($mode, array $descendants, array $expectedEntitiesToProcess)
    {
        $processedEntities = $clearedEntities = [];

        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()->getMock();

        $this->mapper->expects($this->once())->method('getEntityModeConfig')->with(self::TEST_CLASS)
            ->willReturn($mode);
        $this->mapper->expects($descendants ? $this->once() : $this->never())->method('getRegisteredDescendants')
            ->with(self::TEST_CLASS)
            ->willReturn($descendants);

        $this->indexAgent->expects($this->once())->method('initializeClient')
            ->will($this->returnValue($client));
        $this->indexAgent->expects($this->any())->method('recreateTypeMapping')
            ->willReturnCallback(
                function ($client, $class) use (&$clearedEntities) {
                    $clearedEntities[] = $class;
                }
            );

        $engine = $this->getEngineMock();
        $engine->expects($this->any())->method('reindexSingleEntity')
            ->willReturnCallback(
                function ($class) use (&$processedEntities) {
                    $processedEntities[] = $class;
                }
            );

        $engine->reindex(self::TEST_CLASS);
        $this->assertSame($expectedEntitiesToProcess, $processedEntities);
        $this->assertSame($expectedEntitiesToProcess, $clearedEntities);
    }

    /**
     * @return array
     */
    public function entityModeDataProvider()
    {
        return [
            'with normal mode'                => [
                Mode::NORMAL,
                [],
                [self::TEST_CLASS]
            ],
            'with mode only descendants'      => [
                Mode::ONLY_DESCENDANTS,
                [self::TEST_DESCENDANT_1],
                [self::TEST_DESCENDANT_1]
            ],
            'with mode including descendants' => [
                Mode::WITH_DESCENDANTS,
                [self::TEST_DESCENDANT_1, self::TEST_DESCENDANT_2],
                [self::TEST_CLASS, self::TEST_DESCENDANT_1, self::TEST_DESCENDANT_2]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ElasticSearch
     */
    protected function getEngineMock()
    {
        $arguments = [
            $this->registry,
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mapper,
            $this->indexAgent
        ];

        return $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch')
            ->setConstructorArgs($arguments)
            ->setMethods(['reindexSingleEntity'])
            ->getMock();
    }

    /**
     * @param array $response
     * @param array $items
     * @param int $count
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $response, array $items, $count)
    {
        $query = new Query();

        $entityConfiguration = [
            'alias' => self::TEST_ALIAS,
            'fields' => [['name' => 'property', 'target_type' => 'text']]
        ];

        $firstBuilder = $this->getMock('OroPro\Bundle\ElasticSearchBundle\RequestBuilder\RequestBuilderInterface');
        $firstBuilder->expects($this->once())->method('build')
            ->with($query, ['index' => self::TEST_INDEX])
            ->will(
                $this->returnCallback(
                    function (Query $query, array $request) {
                        $request['first'] = true;
                        return $request;
                    }
                )
            );
        $secondBuilder = $this->getMock('OroPro\Bundle\ElasticSearchBundle\RequestBuilder\RequestBuilderInterface');
        $secondBuilder->expects($this->once())->method('build')
            ->with($query, ['index' => self::TEST_INDEX, 'first' => true])
            ->will(
                $this->returnCallback(
                    function (Query $query, array $request) {
                        $request['second'] = true;
                        return $request;
                    }
                )
            );

        $expectedRequest = ['index' => self::TEST_INDEX, 'first' => true, 'second' => true];

        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())->method('search')->with($expectedRequest)
            ->will($this->returnValue($response));

        $this->indexAgent->expects($this->any())->method('getIndexName')
            ->will($this->returnValue(self::TEST_INDEX));
        $this->indexAgent->expects($this->once())->method('initializeClient')
            ->will($this->returnValue($client));

        $this->mapper->expects($this->any())->method('getEntityConfig')->with(self::TEST_CLASS)
            ->will($this->returnValue($entityConfiguration));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())->method('getManagerForClass')->with(self::TEST_CLASS)
            ->will($this->returnValue($entityManager));

        $expectedItems = [];
        foreach ($items as $item) {
            $expectedItems[] = new Item(
                $entityManager,
                $item['class'],
                $item['id'],
                null,
                null,
                $entityConfiguration
            );
        }

        $this->engine->addRequestBuilder($firstBuilder);
        $this->engine->addRequestBuilder($secondBuilder);

        $result = $this->engine->search($query);
        $this->assertEquals($query, $result->getQuery());
        $this->assertEquals($expectedItems, $result->getElements());
        $this->assertEquals($count, $result->getRecordsCount());
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return [
            'valid response' => [
                'response' => [
                    'hits' => [
                        'total' => 5,
                        'hits' => [
                            [
                                '_type' => self::TEST_ALIAS,
                                '_id' => 1,
                                '_source' => [Indexer::TEXT_ALL_DATA_FIELD => 'first']
                            ],
                            ['_type' => self::TEST_ALIAS, '_id' => 2],
                            ['_type' => 'unknown_entity', '_id' => 3],
                            ['_type' => self::TEST_ALIAS],
                        ]
                    ]
                ],
                'items' => [
                    ['class' => self::TEST_CLASS, 'id' => 1],
                    ['class' => self::TEST_CLASS, 'id' => 2],

                ],
                'count' => 5
            ],
            'empty response' => [
                'response' => [
                    'hits' => [
                        'total' => 0,
                        'hits' => []
                    ]
                ],
                'items' => [],
                'count' => 0
            ],
            'invalid response' => [
                'response' => [],
                'items' => [],
                'count' => 0
            ]
        ];
    }
}
