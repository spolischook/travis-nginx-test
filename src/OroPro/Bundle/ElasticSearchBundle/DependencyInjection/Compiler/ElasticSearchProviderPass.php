<?php

namespace OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ElasticSearchProviderPass implements CompilerPassInterface
{
    const ENGINE_PARAMETERS_KEY   = 'oro_search.engine_parameters';

    const SEARCH_ENGINE_HOST      = 'search_engine_host';
    const SEARCH_ENGINE_PORT      = 'search_engine_port';
    const SEARCH_ENGINE_USERNAME  = 'search_engine_username';
    const SEARCH_ENGINE_PASSWORD  = 'search_engine_password';
    const SEARCH_ENGINE_AUTH_TYPE = 'search_engine_auth_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $engineParameters = $container->getParameter(self::ENGINE_PARAMETERS_KEY);
        $engineParameters = $this->processElasticSearchConnection($container, $engineParameters);
        $container->setParameter(self::ENGINE_PARAMETERS_KEY, $engineParameters);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $engineParameters
     * @return array
     */
    protected function processElasticSearchConnection(ContainerBuilder $container, array $engineParameters)
    {
        // connection parameters
        $host = $container->getParameter(self::SEARCH_ENGINE_HOST);
        $port = $container->getParameter(self::SEARCH_ENGINE_PORT);

        if ($host && $port) {
            $host .= ':' . $port;
        }

        if ($host) {
            $engineParameters['connection']['hosts'] = [$host];
        }

        // authentication parameters
        $username = $container->getParameter(self::SEARCH_ENGINE_USERNAME);
        $password = $container->getParameter(self::SEARCH_ENGINE_PASSWORD);
        $authType = $container->getParameter(self::SEARCH_ENGINE_AUTH_TYPE);

        if ($username || $password || $authType) {
            $engineParameters['connection']['connectionParams']['auth'] = array($username, $password, $authType);
        }

        return $engineParameters;
    }

    // TODO: should be move to the initializer service in the scope of OEE-226
    /**
     * @param ContainerBuilder $container
     * @param array            $elasticSearchConfig
     */
/*    protected function processElasticSearchIndex(ContainerBuilder $container, array &$elasticSearchConfig)
    {
        // const DEFAULT_INDEX_NAME = 'default_elastic_search_index'
        if (empty($elasticSearchConfig['index']['index'])) {
            $elasticSearchConfig['index']['index'] = self::DEFAULT_INDEX_NAME;
        }

        // const ENTITIES_CONFIG_KEY = 'oro_search.entities_config';
        $entitiesMapping = $container->getParameter(self::ENTITIES_CONFIG_KEY);
        foreach ($entitiesMapping as $class => $config) {
            if (!empty($config['fields'])) {
                foreach ($config['fields'] as $fieldConfig) {
                    $this->addElasticSearchIndexMapping($class, $fieldConfig, $elasticSearchConfig);
                }
            }
        }
    }
*/

    /**
     * @param string $indexName
     * @param array  $fieldConfig
     * @param array  $elasticSearchConfig
     */
/*  protected function addElasticSearchIndexMapping($indexName, array $fieldConfig, array &$elasticSearchConfig)
    {
        if (!empty($fieldConfig['relation_fields'])) {
            foreach ($fieldConfig['relation_fields'] as $relationFieldConfig) {
                $this->addElasticSearchIndexMapping($indexName, $relationFieldConfig, $elasticSearchConfig);
            }
        } else {
            $name = $fieldConfig['name'];
            $type = $this->getCorrectType($fieldConfig['target_type']);
            $elasticSearchConfig['index']['body']['mappings'][$indexName]['properties'][$name]['type'] = $type;
        }
    }
*/

    /**
     * @param  string $type
     * @return string
     * @throws \Exception
     */
/*    protected function getCorrectType($type)
    {
        $typeConvertRules = array(
            'string'  => array('text', 'string'),
            'integer' => array('integer', 'int', 'long'),
            'float'   => array('decimal', 'double', 'float'),
            'boolean' => array('boolean', 'bool'),
            'date'    => array('date', 'datetime', 'time', 'birthday'),
        );

        foreach ($typeConvertRules as $correctType => $possibleTypes) {
            if (in_array($type, $possibleTypes)) {
                return $correctType;
            }
        }

        throw new \Exception(sprintf('Unsupported type "%s"', $type));
    }
*/
}
