<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use OroPro\Bundle\ElasticSearchBundle\Engine\IndexAgent;

class IndexAgentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $typeMapping = [
        'text' => [
            'type'            => 'string',
            'store'           => true,
            'index' => 'not_analyzed',
            'fields' => [
                'analyzed' => [
                    'type'            => 'string',
                    'search_analyzer' => IndexAgent::FULLTEXT_SEARCH_ANALYZER,
                    'index_analyzer'  => IndexAgent::FULLTEXT_INDEX_ANALYZER
                ]
            ]
        ],
        'decimal' => [
            'type'  => 'double',
            'store' => true,
        ],
        'integer' => [
            'type'  => 'integer',
            'store' => true,
        ],
        'datetime' => [
            'type'   => 'date',
            'store'  => true,
            'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd'
        ],
    ];

    /**
     * @var array
     */
    protected $allTextMapping = [
        'type'            => 'string',
        'store'           => true,
        'index' => 'not_analyzed',
        'fields' => [
            'analyzed' => [
                'type'            => 'string',
                'search_analyzer' => IndexAgent::FULLTEXT_SEARCH_ANALYZER,
                'index_analyzer'  => IndexAgent::FULLTEXT_INDEX_ANALYZER
            ]
        ]
    ];

    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'analyzer' => [
                IndexAgent::FULLTEXT_SEARCH_ANALYZER => [
                    'tokenizer' => 'whitespace',
                    'filter'    => ['lowercase']
                ],
                IndexAgent::FULLTEXT_INDEX_ANALYZER => [
                    'tokenizer' => 'keyword',
                    'filter'    => ['lowercase', 'substring'],
                ],
            ],
            'filter' => [
                'substring' => [
                    'type'     => 'nGram',
                    'min_gram' => 1,
                    'max_gram' => 50
                ]
            ],
        ],
    ];

    /**
     * @param object|null $clientFactory
     * @param array $engineParameters
     * @param array $entityConfiguration
     * @return IndexAgent
     */
    protected function createIndexAgent(
        $clientFactory = null,
        array $engineParameters = [],
        array $entityConfiguration = []
    ) {
        if (!$clientFactory) {
            $clientFactory = $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory')
                ->disableOriginalConstructor()
                ->getMock();
        }

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();
        $mapperProvider = new SearchMappingProvider($eventDispatcher);
        $mapperProvider->setMappingConfig($entityConfiguration);

        return new IndexAgent($clientFactory, $engineParameters, $mapperProvider);
    }

    /**
     * @param array $engineParameters
     * @param $expectedIndexName
     * @dataProvider getIndexNameDataProvider
     */
    public function testGetIndexName(array $engineParameters, $expectedIndexName)
    {
        $indexAgent = $this->createIndexAgent(null, $engineParameters);
        $this->assertEquals($expectedIndexName, $indexAgent->getIndexName());
    }

    /**
     * @return array
     */
    public function getIndexNameDataProvider()
    {
        return [
            'default' => [
                'engineParameters'  => [],
                'expectedIndexName' => IndexAgent::DEFAULT_INDEX_NAME,
            ],
            'custom' => [
                'engineParameters'  => [
                    'index' => [
                        'index' => 'Custom_Index'
                    ]
                ],
                'expectedIndexName' => 'custom_index',
            ],
        ];
    }

    /**
     * @param array $engineParameters
     * @param array $entityConfiguration
     * @param array $clientConfiguration
     * @param array $indexConfiguration
     * @dataProvider initializeClientDataProvider
     */
    public function testInitializeClient(
        array $engineParameters,
        array $entityConfiguration,
        array $clientConfiguration,
        array $indexConfiguration
    ) {
        $indices = $this->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices->expects($this->at(0))->method('exists')->with(['index' => $indexConfiguration['index']])
            ->will($this->returnValue(false));
        $indices->expects($this->at(1))->method('create')->with($indexConfiguration);
        $indices->expects($this->at(2))->method('exists')->with(['index' => $indexConfiguration['index']])
            ->will($this->returnValue(true));

        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->any())->method('indices')
            ->will($this->returnValue($indices));

        $clientFactory = $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $clientFactory->expects($this->exactly(2))->method('create')->with($clientConfiguration)
            ->will($this->returnValue($client));

        $indexAgent = $this->createIndexAgent($clientFactory, $engineParameters, $entityConfiguration);
        $indexAgent->setFieldTypeMapping($this->typeMapping);

        // index must be created only once on first initialization
        $this->assertEquals($client, $indexAgent->initializeClient());
        $this->assertEquals($client, $indexAgent->initializeClient());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function initializeClientDataProvider()
    {
        $minGram = 4;
        $maxGram = 20;

        $customizedSettings = $this->settings;
        $customizedSettings['analysis']['filter']['substring']['min_gram'] = $minGram;
        $customizedSettings['analysis']['filter']['substring']['max_gram'] = $maxGram;

        return [
            'minimum' => [
                'engineParameters' => [],
                'entityConfiguration' => [
                    'Test\Entity' => [
                        'alias' => 'oro_test_entity',
                        'fields' => [['name' => 'property', 'target_type' => 'text']]
                    ]
                ],
                'clientConfiguration' => [],
                'indexConfiguration' => [
                    'index' => IndexAgent::DEFAULT_INDEX_NAME,
                    'body' => [
                        'settings' => $this->settings,
                        'mappings' => [
                            'oro_test_entity' => [
                                'properties' => [
                                    'property' => $this->typeMapping['text'],
                                    'all_text' => $this->allTextMapping,
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'maximum' => [
                'engineParameters' => [
                    'client' => [
                        'hosts' => ['1.2.3.4'],
                        'logging' => true,
                    ],
                    'index' => [
                        'index' => 'custom_index_name',
                        'body' => [
                            'settings' => [
                                'analysis' => [
                                    'filter' => ['substring' => ['min_gram' => $minGram, 'max_gram' => $maxGram]]
                                ]
                            ]
                        ],
                    ]
                ],
                'entityConfiguration' => [
                    'Test\Entity' => [
                        'alias' => 'oro_test_entity',
                        'fields' => [
                            ['name' => 'name',      'target_type' => 'text'],
                            ['name' => 'price',     'target_type' => 'decimal'],
                            ['name' => 'count',     'target_type' => 'integer'],
                            ['name' => 'createdAt', 'target_type' => 'datetime'],
                            [
                                'name'            => 'relatedEntity',
                                'relation_fields' => [
                                    ['name' => 'firstName', 'target_type' => 'text'],
                                    ['name' => 'lastName',  'target_type' => 'text'],
                                ]
                            ]
                        ],
                    ],
                ],
                'clientConfiguration' => [
                    'hosts' => ['1.2.3.4'],
                    'logging' => true,
                ],
                'indexConfiguration' => [
                    'index' => 'custom_index_name',
                    'body' => [
                        'settings' => $customizedSettings,
                        'mappings' => [
                            'oro_test_entity' => [
                                'properties' => [
                                    'name'      => $this->typeMapping['text'],
                                    'price'     => $this->typeMapping['decimal'],
                                    'count'     => $this->typeMapping['integer'],
                                    'createdAt' => $this->typeMapping['datetime'],
                                    'firstName' => $this->typeMapping['text'],
                                    'lastName'  => $this->typeMapping['text'],
                                    'all_text'  => $this->allTextMapping,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Type mapping for type "unknown_type" is not defined
     */
    public function testInitializeClientWithUnknownTypeMapping()
    {
        $entityConfiguration = [
            'Test\Entity' => [
                'alias' => 'oro_test_entity',
                'fields' => [['name' => 'property', 'target_type' => 'unknown_type']]
            ]
        ];

        $indices = $this->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices->expects($this->once())->method('exists')->with(['index' => IndexAgent::DEFAULT_INDEX_NAME])
            ->will($this->returnValue(false));
        $indices->expects($this->never())->method('create');

        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->any())->method('indices')
            ->will($this->returnValue($indices));

        $clientFactory = $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $clientFactory->expects($this->once())->method('create')->with([])
            ->will($this->returnValue($client));

        $indexAgent = $this->createIndexAgent($clientFactory, [], $entityConfiguration);
        $indexAgent->initializeClient();
    }

    /**
     * @param $isIndexExists
     * @dataProvider recreateIndexDataProvider
     */
    public function testRecreateIndex($isIndexExists)
    {
        $entityConfiguration = [
            'Test\Entity' => [
                'alias' => 'oro_test_entity',
                'fields' => [['name' => 'property', 'target_type' => 'text']]
            ]
        ];
        $indexConfiguration = [
            'index' => IndexAgent::DEFAULT_INDEX_NAME,
            'body' => [
                'settings' => $this->settings,
                'mappings' => [
                    'oro_test_entity' => [
                        'properties' => [
                            'property' => $this->typeMapping['text'],
                            'all_text' => $this->allTextMapping,
                        ],
                    ],
                ],
            ],
        ];

        $indices = $this->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices->expects($this->once())->method('exists')->with(['index' => IndexAgent::DEFAULT_INDEX_NAME])
            ->will($this->returnValue($isIndexExists));
        if ($isIndexExists) {
            $indices->expects($this->once())->method('delete')->with(['index' => IndexAgent::DEFAULT_INDEX_NAME]);
        } else {
            $indices->expects($this->never())->method('delete');
        }
        $indices->expects($this->once())->method('create')->with($indexConfiguration)
            ->will($this->returnValue($isIndexExists));

        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->any())->method('indices')
            ->will($this->returnValue($indices));

        $clientFactory = $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $clientFactory->expects($this->once())->method('create')->with([])
            ->will($this->returnValue($client));

        $indexAgent = $this->createIndexAgent($clientFactory, [], $entityConfiguration);
        $indexAgent->setFieldTypeMapping($this->typeMapping);
        $this->assertEquals($client, $indexAgent->recreateIndex());
    }

    /**
     * @return array
     */
    public function recreateIndexDataProvider()
    {
        return [
            'index exists'     => [true],
            'index not exists' => [false],
        ];
    }

    public function testRecreateTypeMapping()
    {
        $entityName = 'Test\Entity';
        $type = 'oro_test_entity';
        $entityConfiguration = [
            $entityName => [
                'alias' => $type,
                'fields' => [['name' => 'property', 'target_type' => 'text']]
            ]
        ];
        $body = [
            'properties' => [
                'property' => $this->typeMapping['text'],
                'all_text' => $this->allTextMapping,
            ],
        ];

        $indices = $this->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices->expects($this->once())->method('existsType')
            ->with(['index' => IndexAgent::DEFAULT_INDEX_NAME, 'type' => $type])
            ->willReturn(true);
        $indices->expects($this->once())->method('deleteMapping')
            ->with(['index' => IndexAgent::DEFAULT_INDEX_NAME, 'type' => $type]);
        $indices->expects($this->once())->method('putMapping')
            ->with(['index' => IndexAgent::DEFAULT_INDEX_NAME, 'type' => $type, 'body' => $body]);

        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->any())->method('indices')
            ->will($this->returnValue($indices));

        $indexAgent = $this->createIndexAgent(null, [], $entityConfiguration);
        $indexAgent->setFieldTypeMapping($this->typeMapping);
        $indexAgent->recreateTypeMapping($client, $entityName);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Search configuration for UnknownEntity is not defined
     */
    public function testRecreateTypeMappingUnknownEntity()
    {
        $client = $this->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $indexAgent = $this->createIndexAgent(null, [], []);
        $indexAgent->setFieldTypeMapping($this->typeMapping);
        $indexAgent->recreateTypeMapping($client, 'UnknownEntity');
    }
}
