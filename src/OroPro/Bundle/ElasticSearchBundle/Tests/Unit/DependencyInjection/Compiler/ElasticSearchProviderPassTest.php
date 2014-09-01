<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\DependencyInjection\Compiler;

use OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler\ElasticSearchProviderPass;

class ElasticSearchProviderPassTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_HOST      = '127.0.0.1';
    const DEFAULT_PORT      = '9200';
    const DEFAULT_USERNAME  = 'username';
    const DEFAULT_PASSWORD  = '1234567';
    const DEFAULT_AUTH_TYPE = 'basic';

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
        $parameters = array(
            ElasticSearchProviderPass::SEARCH_ENGINE_HOST      => self::DEFAULT_HOST,
            ElasticSearchProviderPass::SEARCH_ENGINE_PORT      => self::DEFAULT_PORT,
            ElasticSearchProviderPass::SEARCH_ENGINE_USERNAME  => self::DEFAULT_USERNAME,
            ElasticSearchProviderPass::SEARCH_ENGINE_PASSWORD  => self::DEFAULT_PASSWORD,
            ElasticSearchProviderPass::SEARCH_ENGINE_AUTH_TYPE => self::DEFAULT_AUTH_TYPE,
        );

        return array(
            'empty global configuration' => array(
                'parameters' => array_merge(array(
                    ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY => array(),
                ), $parameters),
                'elasticSearchConfiguration' => array(
                    'client' => array(
                        'hosts'            => array(self::DEFAULT_HOST . ':' . self::DEFAULT_PORT),
                        'connectionParams' => array(
                            'auth' => array(
                                self::DEFAULT_USERNAME,
                                self::DEFAULT_PASSWORD,
                                self::DEFAULT_AUTH_TYPE
                            )
                        )
                    )
                )
            ),

            'not empty global configuration and parameters still not null' => array(
                'parameters' => array_merge(array(
                    ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY  => array(
                        'client' => array(
                            'hosts'            => array('someTestHost:port'),
                            'connectionParams' => array(
                                'auth' => array('name', 'password', 'other-type')
                            )
                        ),
                    ),
                ), $parameters),
                'elasticSearchConfiguration' => array(
                    'client' => array(
                        'hosts'            => array(self::DEFAULT_HOST . ':' . self::DEFAULT_PORT),
                        'connectionParams' => array(
                            'auth' => array(
                                self::DEFAULT_USERNAME,
                                self::DEFAULT_PASSWORD,
                                self::DEFAULT_AUTH_TYPE
                            )
                        )
                    ),
                )
            ),

            'not empty global configuration; Host and Auth parameters still null' => array(
                'parameters' => array(
                    ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY  => array(
                        'client' => array(
                            'hosts'            => array('someTestHost:port'),
                            'connectionParams' => array(
                                'auth' => array('name', 'password', 'other-type')
                            )
                        ),
                    ),
                    ElasticSearchProviderPass::SEARCH_ENGINE_HOST      => null,
                    ElasticSearchProviderPass::SEARCH_ENGINE_PORT      => self::DEFAULT_PORT,
                    ElasticSearchProviderPass::SEARCH_ENGINE_USERNAME  => null,
                    ElasticSearchProviderPass::SEARCH_ENGINE_PASSWORD  => null,
                    ElasticSearchProviderPass::SEARCH_ENGINE_AUTH_TYPE => null,
                ),
                'elasticSearchConfiguration' => array(
                    'client' => array(
                        'hosts'            => array('someTestHost:port'),
                        'connectionParams' => array(
                            'auth' => array('name', 'password', 'other-type')
                        )
                    ),
                )
            ),

            'not empty global configuration; Only one Auth parameters still not null' => array(
                'parameters' => array(
                    ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY  => array(
                        'client' => array(
                            'hosts'            => array('someTestHost:port'),
                            'connectionParams' => array(
                                'auth' => array('name', 'password', 'other-type')
                            )
                        ),
                    ),
                    ElasticSearchProviderPass::SEARCH_ENGINE_HOST      => self::DEFAULT_HOST,
                    ElasticSearchProviderPass::SEARCH_ENGINE_PORT      => 'port',
                    ElasticSearchProviderPass::SEARCH_ENGINE_USERNAME  => null,
                    ElasticSearchProviderPass::SEARCH_ENGINE_PASSWORD  => null,
                    ElasticSearchProviderPass::SEARCH_ENGINE_AUTH_TYPE => self::DEFAULT_AUTH_TYPE,
                ),
                'elasticSearchConfiguration' => array(
                    'client' => array(
                        'hosts'            => array(self::DEFAULT_HOST . ':port'),
                        'connectionParams' => array(
                            'auth' => array(null, null, self::DEFAULT_AUTH_TYPE)
                        )
                    ),
                )
            ),
        );
    }
}
