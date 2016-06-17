<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Elasticsearch\Client;

use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Engine\AbstractEngine;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\RequestBuilderInterface;

class ElasticSearch extends AbstractEngine
{
    const ENGINE_NAME = 'elastic_search';

    /** @var IndexAgent */
    protected $indexAgent;

    /** @var Client */
    protected $client;

    /** @var RequestBuilderInterface[] */
    protected $requestBuilders = [];

    /**
     * @param ManagerRegistry          $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper           $doctrineHelper
     * @param ObjectMapper             $mapper
     * @param IndexAgent               $indexAgent
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        IndexAgent $indexAgent
    ) {
        parent::__construct($registry, $eventDispatcher, $doctrineHelper, $mapper);

        $this->indexAgent = $indexAgent;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $realTime = true)
    {
        return $this->processEntities($entity, $realTime, true);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $realTime = true)
    {
        return $this->processEntities($entity, $realTime, false);
    }

    /**
     * @param object|array $entity
     * @param bool         $realTime
     * @param bool         $isSave
     * @return bool
     */
    protected function processEntities($entity, $realTime, $isSave)
    {
        $entities = $this->getEntitiesArray($entity);
        if (!$entities) {
            return false;
        }

        if (!$realTime) {
            $this->scheduleIndexation($entities);

            return true;
        }

        $body = [];

        foreach ($entities as $entity) {
            $type = $this->getEntityAlias($this->doctrineHelper->getEntityClass($entity));
            $id   = (string) $this->doctrineHelper->getSingleEntityIdentifier($entity);
            if (!$type || !$id) {
                continue;
            }

            // need to recreate index to avoid saving of not used fields
            $indexIdentifier = ['_type' => $type, '_id' => $id];
            $body[]          = ['delete' => $indexIdentifier];

            if ($isSave) {
                $indexData = $this->getIndexData($entity);
                if ($indexData) {
                    $body[] = ['create' => $indexIdentifier];
                    $body[] = $indexData;
                }
            }
        }

        if (!$body) {
            return false;
        }

        $response = $this->getClient()->bulk(['index' => $this->indexAgent->getIndexName(), 'body' => $body]);

        return empty($response['errors']);
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, $offset = null, $limit = null)
    {
        if (null === $class) {
            $this->client = $this->indexAgent->recreateIndex();
            $entityNames  = $this->mapper->getEntities([Mode::NORMAL, Mode::WITH_DESCENDANTS]);
        } else {
            $entityNames = [$class];
            $mode        = $this->mapper->getEntityModeConfig($class);
            if ($mode === Mode::WITH_DESCENDANTS) {
                $entityNames = array_merge($entityNames, $this->mapper->getRegisteredDescendants($class));
            } elseif ($mode === Mode::ONLY_DESCENDANTS) {
                $entityNames = $this->mapper->getRegisteredDescendants($class);
            }

            if ((null === $offset && null === $limit) || ($offset === 0 && $limit)) {
                foreach ($entityNames as $class) {
                    $this->indexAgent->recreateTypeMapping($this->getClient(), $class);
                }
            }
        }

        $recordsCount = 0;

        while ($entityName = array_shift($entityNames)) {
            $recordsCount += $this->reindexSingleEntity($entityName);
        }

        return $recordsCount;
    }

    /**
     * @param RequestBuilderInterface $requestBuilder
     */
    public function addRequestBuilder(RequestBuilderInterface $requestBuilder)
    {
        $this->requestBuilders[] = $requestBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSearch(Query $query)
    {
        $request = ['index' => $this->indexAgent->getIndexName()];

        foreach ($this->requestBuilders as $requestBuilder) {
            $request = $requestBuilder->build($query, $request);
        }

        $response = $this->getClient()->search($request);

        $results = [];
        if (!empty($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $item = $this->convertHitToItem($hit);
                if ($item) {
                    $results[] = $item;
                }
            }
        }

        $recordsCount = !empty($response['hits']['total']) ? $response['hits']['total'] : 0;

        return ['results' => $results, 'records_count' => $recordsCount];
    }

    /**
     * @param array $hit
     * @return null|Item
     */
    protected function convertHitToItem(array $hit)
    {
        $type = null;
        if (!empty($hit['_type'])) {
            $type = $hit['_type'];
        }

        $id = null;
        if (!empty($hit['_id'])) {
            $id = $hit['_id'];
        }

        if (!$type || !$id) {
            return null;
        }

        $entityName = $this->getEntityName($type);
        if (!$entityName) {
            return null;
        }

        return new Item(
            $this->registry->getManagerForClass($entityName),
            $entityName,
            $id,
            null,
            null,
            $this->mapper->getEntityConfig($entityName)
        );
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = $this->indexAgent->initializeClient();
        }

        return $this->client;
    }

    /**
     * @param string $class
     * @return string|null
     */
    protected function getEntityAlias($class)
    {
        $entitiesToAliases = $this->mapper->getEntitiesListAliases();

        return !empty($entitiesToAliases[$class]) ? $entitiesToAliases[$class] : null;
    }

    /**
     * @param string $alias
     * @return string|null
     */
    protected function getEntityName($alias)
    {
        $aliasesToEntities = array_flip($this->mapper->getEntitiesListAliases());

        return !empty($aliasesToEntities[$alias]) ? $aliasesToEntities[$alias] : null;
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function getIndexData($entity)
    {
        $indexData = [];
        foreach ($this->mapper->mapObject($entity) as $fields) {
            $indexData = array_merge($indexData, $fields);
        }

        foreach ($indexData as $key => $value) {
            if ($value instanceof \DateTime) {
                $value->setTimezone(new \DateTimeZone('UTC'));
                $indexData[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $indexData[$key] = (string) $value;
            }
        }

        return $indexData;
    }
}
