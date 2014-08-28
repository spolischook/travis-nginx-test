<?php

namespace OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ElasticSearchProviderPass implements CompilerPassInterface
{
    const DEFAULT_INDEX_NAME      = 'defaultElasticSearchIndex';

    const ENGINE_PARAMETERS_KEY   = 'oro_search.engine_parameters';
    const ENTITIES_CONFIG_KEY     = 'oro_search.entities_config';

    const SEARCH_ENGINE_HOST      = 'search_engine_host';
    const SEARCH_ENGINE_PORT      = 'search_engine_port';
    const SEARCH_ENGINE_USERNAME  = 'search_engine_username';
    const SEARCH_ENGINE_PASSWORD  = 'search_engine_password';
    const SEARCH_ENGINE_AUTH_TYPE = 'search_engine_auth_type';

    public function process(ContainerBuilder $container)
    {
        $elasticSearchConfig = $container->getParameter(self::ENGINE_PARAMETERS_KEY);
        $this->processElasticSearchConnection($container, $elasticSearchConfig);
        $this->processElasticSearchIndex($container, $elasticSearchConfig);

        $container->setParameter(self::ENGINE_PARAMETERS_KEY, $elasticSearchConfig);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $elasticSearchConfig
     */
    protected function processElasticSearchConnection(ContainerBuilder $container, array &$elasticSearchConfig)
    {
        $host = $container->getParameter(self::SEARCH_ENGINE_HOST);
        $port = $container->getParameter(self::SEARCH_ENGINE_PORT);

        if (!empty($port)) {
            $host .= ':' . $port;
        }

        // fill connection parameters
        $elasticSearchConfig['connection']['hosts']                    = [$host];
        $elasticSearchConfig['connection']['connectionParams']['auth'] = array(
            $container->getParameter(self::SEARCH_ENGINE_USERNAME),
            $container->getParameter(self::SEARCH_ENGINE_PASSWORD),
            $container->getParameter(self::SEARCH_ENGINE_AUTH_TYPE)
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $elasticSearchConfig
     */
    protected function processElasticSearchIndex(ContainerBuilder $container, array &$elasticSearchConfig)
    {
        if (empty($elasticSearchConfig['index']['index'])) {
            $elasticSearchConfig['index']['index'] = self::DEFAULT_INDEX_NAME;
        }

        $entitiesMapping = $container->getParameter(self::ENTITIES_CONFIG_KEY);
        foreach ($entitiesMapping as $class => $config) {
            if (!empty($config['fields'])) {
                foreach ($config['fields'] as $fieldConfig) {
                    $this->addElasticSearchIndexMapping($class, $fieldConfig, $elasticSearchConfig);
                }
            }
        }
    }

    /**
     * @param string $indexName
     * @param array  $fieldConfig
     * @param array  $elasticSearchConfig
     */
    protected function addElasticSearchIndexMapping($indexName, array $fieldConfig, array &$elasticSearchConfig)
    {
        if (!empty($fieldConfig['relation_fields'])) {
            foreach ($fieldConfig['relation_fields'] as $relationFieldConfig) {
                $this->addElasticSearchIndexMapping($indexName, $relationFieldConfig, $elasticSearchConfig);
            }
        } else {
            $name = $fieldConfig['name'];
            $type = $fieldConfig['target_type'];
            $elasticSearchConfig['index']['body']['mappings'][$indexName]['properties'][$name]['type'] = $type;
        }
    }
}
