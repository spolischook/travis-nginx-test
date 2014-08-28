<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\DependencyInjection\Compiler;

use OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler\ElasticSearchProviderPass;

class ElasticSearchProviderPassTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_HOST      = 'localhost';
    const DEFAULT_PORT      = null;
    const DEFAULT_USERNAME  = 'username';
    const DEFAULT_PASSWORD  = '1234567';
    const DEFAULT_AUTH_TYPE = 'basic';

    /**
     * @var array
     */
    private $testMapping = array(
        'Oro\Bundle\TestFrameworkBundle\Entity\Item' => array(
            'label'           => 'Test Search Bundle Item',
            'alias'           => 'oro_test_item',
            'search_template' => 'OroSearchBundle:Test:searchResult.html.twig',
            'route'           => array('name' => 'oro_search_results'),
            'fields'          => array(
                array(
                    'name'          => 'stringValue',
                    'target_type'   => 'text',
                    'target_fields' => array('stringValue', 'all_data'),
                ),
                array(
                    'name'            => 'relatedContact',
                    'relation_type'   => 'many-to-one',
                    'relation_fields' => array(
                        array(
                            'name'          => 'namePrefix',
                            'target_type'   => 'text',
                            'target_fields' => array('namePrefix'),
                        ),
                        array(
                            'name'          => 'firstName',
                            'target_type'   => 'text',
                            'target_fields' => array('firstName'),
                        )
                    )
                )
            )
        )
    );

    /**
     * @var ElasticSearchProviderPass
     */
    protected $compiler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compiler = new ElasticSearchProviderPass();
    }

    /**
     * @dataProvider processProvider
     * @param array $parameters
     * @param array $elasticSearchConfiguration
     */
    public function testProcess($parameters, $elasticSearchConfiguration)
    {
        $callOrder = 0;
        foreach ($parameters as $parameter => $value) {
            $this->container->expects($this->at($callOrder++))
                ->method('getParameter')
                ->with($parameter)
                ->will($this->returnValue($value));
        }

        $this->container->expects($this->at($callOrder))
            ->method('setParameter')
            ->with(ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY, $elasticSearchConfiguration);

        $this->compiler->process($this->container);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function processProvider()
    {
        $class      = key($this->testMapping);
        $parameters = array(
            ElasticSearchProviderPass::SEARCH_ENGINE_HOST      => self::DEFAULT_HOST,
            ElasticSearchProviderPass::SEARCH_ENGINE_PORT      => self::DEFAULT_PORT,
            ElasticSearchProviderPass::SEARCH_ENGINE_USERNAME  => self::DEFAULT_USERNAME,
            ElasticSearchProviderPass::SEARCH_ENGINE_PASSWORD  => self::DEFAULT_PASSWORD,
            ElasticSearchProviderPass::SEARCH_ENGINE_AUTH_TYPE => self::DEFAULT_AUTH_TYPE,
            ElasticSearchProviderPass::ENTITIES_CONFIG_KEY     => $this->testMapping,
        );

        return array(
            'empty global configuration' => array(
                'parameters' => array_merge(array(
                    ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY => array(),
                ), $parameters),
                'elasticSearchConfiguration' => array(
                    'connection' => array(
                        'hosts'            => array('localhost'),
                        'connectionParams' => array(
                            'auth' => array(
                                self::DEFAULT_USERNAME,
                                self::DEFAULT_PASSWORD,
                                self::DEFAULT_AUTH_TYPE
                            )
                        )
                    ),
                    'index' => array(
                        'index' => ElasticSearchProviderPass::DEFAULT_INDEX_NAME,
                        'body' => array(
                            'mappings' => array(
                                $class => array(
                                    'properties' => array(
                                        'stringValue' => array('type' => 'text'),
                                        'namePrefix'  => array('type' => 'text'),
                                        'firstName'   => array('type' => 'text'),
                                    ),
                                )
                            )
                        )
                    )
                )
            ),

            'not empty global configuration (parameters wins other value should be merge)' => array(
                'parameters' => array_merge(array(
                    ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY  => array(
                        'connection' => array(
                            'hosts'            => array('someTestHost:port'),
                            'connectionParams' => array(
                                'auth' => array('name', 'password', 'other-type')
                            )
                        ),
                        'index' => array(
                            'index' => 'custom name',
                            'body' => array(
                                'mappings' => array(
                                    $class => array(
                                        'properties' => array(
                                            'additionField' => array('type' => 'integer'),
                                            'firstName'     => array('type' => 'integer'),
                                        )
                                    ),
                                    '\Other\Class' => array(
                                        'properties' => array(
                                            'additionField' => array('type' => 'integer'),
                                            'firstName'     => array('type' => 'integer'),
                                        )
                                    ),
                                )
                            )
                        )
                    ),
                ), $parameters),
                'elasticSearchConfiguration' => array(
                    'connection' => array(
                        'hosts'            => array('localhost'),
                        'connectionParams' => array(
                            'auth' => array(
                                self::DEFAULT_USERNAME,
                                self::DEFAULT_PASSWORD,
                                self::DEFAULT_AUTH_TYPE
                            )
                        )
                    ),
                    'index' => array(
                        'index' => 'custom name',
                        'body' => array(
                            'mappings' => array(
                                '\Other\Class' => array(
                                    'properties' => array(
                                        'additionField' => array('type' => 'integer'),
                                        'firstName'     => array('type' => 'integer'),
                                    )
                                ),
                                $class => array(
                                    'properties' => array(
                                        'additionField' => array('type' => 'integer'),
                                        'stringValue'   => array('type' => 'text'),
                                        'namePrefix'    => array('type' => 'text'),
                                        'firstName'     => array('type' => 'text'),
                                    ),
                                )
                            )
                        )
                    )
                )
            ),
        );
    }
}
