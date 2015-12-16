<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Elasticsearch\Client;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory;

class IndexAgent
{
    const DEFAULT_INDEX_NAME = 'oro_search';

    const FULLTEXT_SEARCH_ANALYZER = 'fulltext_search_analyzer';
    const FULLTEXT_INDEX_ANALYZER  = 'fulltext_index_analyzer';

    const FULLTEXT_ANALYZED_FIELD = 'analyzed';

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var array
     */
    protected $engineParameters;

    /**
     * @var SearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @var array
     */
    protected $fieldTypeMapping = [];

    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'analyzer' => [
                self::FULLTEXT_SEARCH_ANALYZER => [
                    'tokenizer' => 'whitespace',
                    'filter'    => ['lowercase']
                ],
                self::FULLTEXT_INDEX_ANALYZER  => [
                    'tokenizer' => 'keyword',
                    'filter'    => ['lowercase', 'substring'],
                ],
            ],
            'filter'   => [
                'substring' => [
                    'type'     => 'nGram',
                    'min_gram' => 1,
                    'max_gram' => 50
                ]
            ],
        ],
    ];

    /**
     * For text fields we should create non analysed field for strict search (=, != operators)
     * and subfield 'analyzed' for fuzzy search (~, !~ operators)
     *
     * @var array
     */
    protected $textFieldConfig = [
        'type'   => 'string',
        'store'  => true,
        'index'  => 'not_analyzed',
        'fields' => [
            self::FULLTEXT_ANALYZED_FIELD => [
                'type'            => 'string',
                'search_analyzer' => self::FULLTEXT_SEARCH_ANALYZER,
                'index_analyzer'  => self::FULLTEXT_INDEX_ANALYZER
            ]
        ]
    ];

    /**
     * @param ClientFactory         $clientFactory
     * @param array                 $engineParameters
     * @param SearchMappingProvider $mappingProvider
     */
    public function __construct(
        ClientFactory $clientFactory,
        array $engineParameters,
        SearchMappingProvider $mappingProvider
    ) {
        $this->clientFactory    = $clientFactory;
        $this->engineParameters = $engineParameters;
        $this->mappingProvider  = $mappingProvider;
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
     * @return Client
     */
    public function recreateIndex()
    {
        $client = $this->clientFactory->create($this->getClientConfiguration());

        $indexName = $this->getIndexName();
        if ($this->isIndexExists($client, $indexName)) {
            $client->indices()->delete(['index' => $indexName]);
        }

        $client->indices()->create($this->getIndexConfiguration());

        return $client;
    }

    /**
     * @param Client $client
     * @param string $entityName
     */
    public function recreateTypeMapping(Client $client, $entityName)
    {
        $typeMapping = $this->getTypeMapping($entityName);
        $type        = current(array_keys($typeMapping));
        $body        = current(array_values($typeMapping));

        $indexName = $this->getIndexName();
        if ($client->indices()->existsType(['index' => $indexName, 'type' => $type])) {
            $client->indices()->deleteMapping(['index' => $indexName, 'type' => $type]);
        }
        $client->indices()->putMapping(['index' => $indexName, 'type' => $type, 'body' => $body]);
    }

    /**
     * @return array
     */
    protected function getClientConfiguration()
    {
        if (!empty($this->engineParameters['client'])) {
            return $this->engineParameters['client'];
        }

        return [];
    }

    /**
     * @param Client $client
     * @param string $indexName
     *
     * @return bool
     */
    protected function isIndexExists(Client $client, $indexName)
    {
        return $client->indices()->exists(['index' => $indexName]);
    }

    /**
     * @return array
     */
    protected function getIndexConfiguration()
    {
        $indexConfiguration = [];
        if (!empty($this->engineParameters['index'])) {
            $indexConfiguration = $this->engineParameters['index'];
        }

        // process index name
        if (empty($indexConfiguration['index'])) {
            $indexConfiguration['index'] = $this->getIndexName();
        }

        // process settings
        if (empty($indexConfiguration['body']['settings'])) {
            $indexConfiguration['body']['settings'] = [];
        }
        $indexConfiguration['body']['settings']
            = array_replace_recursive($this->getSettings(), $indexConfiguration['body']['settings']);

        // process mappings
        if (empty($indexConfiguration['body']['mappings'])) {
            $indexConfiguration['body']['mappings'] = [];
        }
        $indexConfiguration['body']['mappings']
            = array_replace_recursive($this->getMappings(), $indexConfiguration['body']['mappings']);

        return $indexConfiguration;
    }

    /**
     * @param array $mapping
     */
    public function setFieldTypeMapping(array $mapping)
    {
        $this->fieldTypeMapping = $mapping;
    }

    /**
     * @param string $type
     *
     * @return array
     * @throws \LogicException
     */
    protected function getFieldTypeMapping($type)
    {
        if (!array_key_exists($type, $this->fieldTypeMapping)) {
            throw new \LogicException(sprintf('Type mapping for type "%s" is not defined', $type));
        }

        return $this->fieldTypeMapping[$type];
    }

    /**
     * @return array
     */
    protected function getMappings()
    {
        $mappings = [];
        foreach (array_keys($this->mappingProvider->getMappingConfig()) as $entityName) {
            $mappings = array_merge($mappings, $this->getTypeMapping($entityName));
        }

        return $mappings;
    }

    /**
     * @param string $entityName
     *
     * @return array
     * @throws \LogicException
     */
    protected function getTypeMapping($entityName)
    {
        if (empty($this->mappingProvider->getMappingConfig()[$entityName])) {
            throw new \LogicException(sprintf('Search configuration for %s is not defined', $entityName));
        }

        $configuration = $this->mappingProvider->getMappingConfig()[$entityName];
        $properties    = [];

        // entity fields properties
        foreach ($this->getFieldsWithTypes($configuration['fields']) as $field => $type) {
            $properties[$field] = $this->getFieldTypeMapping($type);

            if ($type === 'text') {
                $properties[$field] = $this->textFieldConfig;
            }
        }

        // all text field
        $properties[Indexer::TEXT_ALL_DATA_FIELD] = $this->textFieldConfig;

        $alias = $configuration['alias'];

        return [$alias => ['properties' => $properties]];
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function getFieldsWithTypes(array $fields)
    {
        $fieldsWithTypes = [];

        foreach ($fields as $field) {
            if (!empty($field['target_type'])) {
                $targetType   = $field['target_type'];
                $targetFields = isset($field['target_fields']) ? $field['target_fields'] : [$field['name']];
                foreach ($targetFields as $targetField) {
                    $fieldsWithTypes[$targetField] = $targetType;
                }
            } elseif (!empty($field['relation_fields'])) {
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
