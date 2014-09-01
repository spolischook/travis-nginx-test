<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Elasticsearch\Client;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory;

class IndexAgent
{
    const DEFAULT_INDEX_NAME = 'oro_search';

    const FULLTEXT_SEARCH_ANALYZER = 'fulltext_search_analyzer';
    const FULLTEXT_INDEX_ANALYZER  = 'fulltext_index_analyzer';

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var array
     */
    protected $engineParameters;

    /**
     * @var array
     */
    protected $entityConfiguration;

    /**
     * @var array
     */
    protected $typeMapping = array();

    /**
     * @var array
     */
    protected $settings = array(
        'analysis' => array(
            'analyzer' => array(
                self::FULLTEXT_SEARCH_ANALYZER => array(
                    'tokenizer' => 'keyword',
                    'filter'    => array('lowercase')
                ),
                self::FULLTEXT_INDEX_ANALYZER => array(
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

    /**
     * @param ClientFactory $clientFactory
     * @param array $engineParameters
     * @param array $entityConfiguration
     */
    public function __construct(ClientFactory $clientFactory, array $engineParameters, array $entityConfiguration)
    {
        $this->clientFactory       = $clientFactory;
        $this->engineParameters    = $engineParameters;
        $this->entityConfiguration = $entityConfiguration;
    }

    /**
     * @return Client
     */
    public function initializeClient()
    {
        $client = $this->clientFactory->create($this->getClientConfiguration());

        if (!$this->isIndexExists($client, $this->getIndexName())) {
            $client->indices()->create($this->getIndexConfiguration());
        }

        return $client;
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
     * @param Client $client
     * @param string $indexName
     * @return bool
     */
    protected function isIndexExists(Client $client, $indexName)
    {
        return $client->indices()->exists(array('index' => $indexName));
    }

    /**
     * @return string
     */
    protected function getDefaultIndexName()
    {
        return static::DEFAULT_INDEX_NAME;
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

        // process settings
        if (empty($indexConfiguration['body']['settings'])) {
            $indexConfiguration['body']['settings'] = array();
        }
        $indexConfiguration['body']['settings']
            = array_merge_recursive($this->getSettings(), $indexConfiguration['body']['settings']);

        // process mappings
        if (empty($indexConfiguration['body']['mappings'])) {
            $indexConfiguration['body']['mappings'] = array();
        }
        $indexConfiguration['body']['mappings']
            = array_merge_recursive($this->getMappings(), $indexConfiguration['body']['mappings']);

        return $indexConfiguration;
    }

    /**
     * @param array $mapping
     */
    public function setTypeMapping(array $mapping)
    {
        $this->typeMapping = $mapping;
    }

    /**
     * @param string $type
     * @return array
     * @throws \LogicException
     */
    protected function getTypeMapping($type)
    {
        if (!array_key_exists($type, $this->typeMapping)) {
            throw new \LogicException(sprintf('Type mapping for type "%s" is not defined', $type));
        }

        return $this->typeMapping[$type];
    }

    /**
     * @return array
     */
    protected function getMappings()
    {
        $mappings = array();
        foreach ($this->entityConfiguration as $configuration) {
            $properties = array();
            
            // entity fields properties
            foreach ($this->getFieldsWithTypes($configuration['fields']) as $field => $type) {
                $properties[$field] = $this->getTypeMapping($type);
            }

            // all text property with nGram tokenizer
            $properties[Indexer::TEXT_ALL_DATA_FIELD] = array(
                'type'            => 'string',
                'store'           => true,
                'search_analyzer' => self::FULLTEXT_SEARCH_ANALYZER,
                'index_analyzer'  => self::FULLTEXT_INDEX_ANALYZER
            );

            $alias = $configuration['alias'];
            $mappings[$alias] = array('properties' => $properties);
        }

        return $mappings;
    }

    /**
     * @param array $fields
     * @return array
     */
    protected function getFieldsWithTypes(array $fields)
    {
        $fieldsWithTypes = array();

        foreach ($fields as $field) {
            if (!empty($field['target_type'])) {
                $targetType = $field['target_type'];
                $targetFields = isset($field['target_fields']) ? $field['target_fields'] : array($field['name']);
                foreach ($targetFields as $targetField) {
                    $fieldsWithTypes[$targetField] = $targetType;
                }
            } elseif (!empty($field['relation_type'])) {
                $fieldsWithTypes = array_merge($fieldsWithTypes, $this->getFieldsWithTypes($field['relation_fields']));
            }
        }

        return $fieldsWithTypes;
    }

    /**
     * @return array
     */
    protected function getSettings()
    {
        return $this->settings;
    }
}
