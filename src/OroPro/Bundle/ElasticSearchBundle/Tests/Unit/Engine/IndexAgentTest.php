<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use OroPro\Bundle\ElasticSearchBundle\Engine\IndexAgent;

class IndexAgentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param object|null $clientFactory
     * @param array $engineParameters
     * @param array $entityConfiguration
     * @return IndexAgent
     */
    protected function createIndexAgent(
        $clientFactory = null,
        array $engineParameters = array(),
        array $entityConfiguration = array()
    ) {
        if (!$clientFactory) {
            $clientFactory = $this->getMockBuilder('OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return new IndexAgent($clientFactory, $engineParameters, $entityConfiguration);
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
        return array(
            'default' => array(
                'engineParameters'  => array(),
                'expectedIndexName' => IndexAgent::DEFAULT_INDEX_NAME,
            ),
            'custom' => array(
                'engineParameters'  => array(
                    'index' => array(
                        'index' => 'Custom_Index'
                    )
                ),
                'expectedIndexName' => 'custom_index',
            ),
        );
    }

    /**
     * @param array $engineParameters
     * @param array $entityConfiguration
     * @param array $typeMapping
     * @param array $clientConfiguration
     * @param array $indexConfiguration
     * @dataProvider initializeClientDataProvider
     */
    public function testInitializeClient(
        array $engineParameters,
        array $entityConfiguration,
        array $typeMapping,
        array $clientConfiguration,
        array $indexConfiguration
    ) {
        $indices = $this->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices->expects($this->at(0))->method('exists')->with(array('index' => $indexConfiguration['index']))
            ->will($this->returnValue(false));
        $indices->expects($this->at(1))->method('create')->with($indexConfiguration);
        $indices->expects($this->at(2))->method('exists')->with(array('index' => $indexConfiguration['index']))
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
        $indexAgent->setTypeMapping($typeMapping);

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
        $typeMapping = array(
            'text' => array(
                'type'  => 'string',
                'store' => true,
                'index' => 'not_analyzed'
            ),
            'decimal' => array(
                'type'  => 'double',
                'store' => true,
            ),
            'integer' => array(
                'type'  => 'integer',
                'store' => true,
            ),
            'datetime' => array(
                'type'   => 'date',
                'store'  => true,
                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd'
            ),
        );
        $allTextMapping = array(
            'type'            => 'string',
            'store'           => true,
            'search_analyzer' => IndexAgent::FULLTEXT_SEARCH_ANALYZER,
            'index_analyzer'  => IndexAgent::FULLTEXT_INDEX_ANALYZER
        );
        $settings = array(
            'analysis' => array(
                'analyzer' => array(
                    IndexAgent::FULLTEXT_SEARCH_ANALYZER => array(
                        'tokenizer' => 'keyword',
                        'filter'    => array('lowercase')
                    ),
                    IndexAgent::FULLTEXT_INDEX_ANALYZER => array(
                        'tokenizer' => 'keyword',
                        'filter'    => array('lowercase', 'substring'),
                    ),
                ),
                'filter' => array(
                    'substring' => array(
                        'type'     => 'nGram',
                        'min_gram' => 1,
                        'max_gram' => 30
                    )
                ),
            ),
        );

        return array(
            'minimum' => array(
                'engineParameters' => array(),
                'entityConfiguration' => array(
                    'Test\Entity' => array(
                        'alias' => 'oro_test_entity',
                        'fields' => array(array('name' => 'property', 'target_type' => 'text'))
                    )
                ),
                'typeMapping' => $typeMapping,
                'clientConfiguration' => array(),
                'indexConfiguration' => array(
                    'index' => IndexAgent::DEFAULT_INDEX_NAME,
                    'body' => array(
                        'settings' => $settings,
                        'mappings' => array(
                            'oro_test_entity' => array(
                                'properties' => array(
                                    'property' => $typeMapping['text'],
                                    'all_text' => $allTextMapping,
                                ),
                            ),
                        ),
                    ),
                )
            ),
            'maximum' => array(
                'engineParameters' => array(
                    'client' => array(
                        'hosts' => array('1.2.3.4'),
                        'logging' => true,
                    ),
                    'index' => array(
                        'index' => 'custom_index_name',
                    )
                ),
                'entityConfiguration' => array(
                    'Test\Entity' => array(
                        'alias' => 'oro_test_entity',
                        'fields' => array(
                            array('name' => 'name',      'target_type' => 'text'),
                            array('name' => 'price',     'target_type' => 'decimal'),
                            array('name' => 'count',     'target_type' => 'integer'),
                            array('name' => 'createdAt', 'target_type' => 'datetime'),
                            array(
                                'name'            => 'relatedEntity',
                                'relation_fields' => array(
                                    array('name' => 'firstName', 'target_type' => 'text'),
                                    array('name' => 'lastName',  'target_type' => 'text'),
                                )
                            )
                        ),
                    ),
                ),
                'typeMapping' => $typeMapping,
                'clientConfiguration' => array(
                    'hosts' => array('1.2.3.4'),
                    'logging' => true,
                ),
                'indexConfiguration' => array(
                    'index' => 'custom_index_name',
                    'body' => array(
                        'settings' => $settings,
                        'mappings' => array(
                            'oro_test_entity' => array(
                                'properties' => array(
                                    'name'      => $typeMapping['text'],
                                    'price'     => $typeMapping['decimal'],
                                    'count'     => $typeMapping['integer'],
                                    'createdAt' => $typeMapping['datetime'],
                                    'firstName' => $typeMapping['text'],
                                    'lastName'  => $typeMapping['text'],
                                    'all_text'  => $allTextMapping,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Type mapping for type "unknown_type" is not defined
     */
    public function testInitializeClientWithUnknownTypeMapping()
    {
        $entityConfiguration = array(
            'Test\Entity' => array(
                'alias' => 'oro_test_entity',
                'fields' => array(array('name' => 'property', 'target_type' => 'unknown_type'))
            )
        );

        $indices = $this->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices->expects($this->once())->method('exists')->with(array('index' => IndexAgent::DEFAULT_INDEX_NAME))
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
        $clientFactory->expects($this->once())->method('create')->with(array())
            ->will($this->returnValue($client));

        $indexAgent = $this->createIndexAgent($clientFactory, array(), $entityConfiguration);
        $indexAgent->initializeClient();
    }
}
