<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Elasticsearch\Client;

class Initializer
{
    const DEFAULT_INDEX_NAME = 'oro_search';

    /**
     * @var array
     */
    protected $engineParameters;

    /**
     * @param array $engineParameters
     */
    public function __construct(array $engineParameters)
    {
        $this->engineParameters = $engineParameters;
    }

    /**
     * @return Client
     */
    public function initialize()
    {
        $client = new Client($this->getClientConfiguration());

        if (!$this->isIndexExists($client)) {
            $client->indices()->create($this->getIndexConfiguration());
        }

        return $client;
    }

    /**
     * @param Client $client
     * @return bool
     */
    protected function isIndexExists(Client $client)
    {
        $indexName = $this->getIndexName();
        $aliases = $client->indices()->getAliases();

        return array_key_exists($indexName, $aliases);
    }

    /**
     * @return array
     */
    protected function getClientConfiguration()
    {
        if (!empty($this->engineParameters['client'])) {
            return $this->engineParameters['client'];
        }

        return array();
    }

    /**
     * @return array
     */
    protected function getIndexConfiguration()
    {
        $indexConfiguration = array();
        if (!empty($this->engineParameters['index'])) {
            $indexConfiguration = $this->engineParameters['index'];
        }

        // process index name
        if (empty($indexConfiguration['index'])) {
            $indexConfiguration['index'] = $this->getIndexName();
        }

        // process entity mappings
        // TODO

        return $indexConfiguration;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        $indexName = self::DEFAULT_INDEX_NAME;
        if (!empty($this->engineParameters['index']['index'])) {
            $indexName = $this->engineParameters['index']['index'];
        }

        // index name must be lowercase
        return strtolower($indexName);
    }

    /**
     * @return string
     */
    protected function getDefaultIndexName()
    {
        return static::DEFAULT_INDEX_NAME;
    }
}
