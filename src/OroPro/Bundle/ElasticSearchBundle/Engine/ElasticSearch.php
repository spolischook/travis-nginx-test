<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Doctrine\Common\Persistence\ManagerRegistry;

use Elasticsearch\Client;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Engine\AbstractEngine;

class ElasticSearch extends AbstractEngine
{
    /**
     * @var IndexAgent
     */
    protected $indexAgent;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcher $dispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param ObjectMapper $mapper
     * @param IndexAgent $indexAgent
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcher $dispatcher,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        IndexAgent $indexAgent
    ) {
        parent::__construct($registry, $dispatcher, $doctrineHelper, $mapper);

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
     * @param bool $realTime
     * @param bool $isSave
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

        $body = array();

        foreach ($entities as $entity) {
            $type = $this->getEntityAlias($this->doctrineHelper->getEntityClass($entity));
            $id   = (string)$this->doctrineHelper->getSingleEntityIdentifier($entity);
            if (!$type || !$id) {
                continue;
            }

            // need to recreate index to avoid saving of not used fields
            $indexIdentifier = array('_type' => $type, '_id' => $id);
            $body[] = array('delete' => $indexIdentifier);

            if ($isSave) {
                $indexData = $this->getIndexData($entity);
                if (!$indexData) {
                    continue;
                }

                $body[] = array('create' => $indexIdentifier);
                $body[] = $indexData;
            }
        }

        if (!$body) {
            return false;
        }

        $response = $this->getClient()->bulk(array('index' => $this->indexAgent->getIndexName(), 'body' => $body));

        return empty($response['errors']);
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null)
    {
        if (null === $class) {
            $this->client = $this->indexAgent->recreateIndex();
            $entityNames = $this->mapper->getEntities();
        } else {
            $this->indexAgent->recreateTypeMapping($this->getClient(), $class);
            $entityNames = array($class);
        }

        $recordsCount = 0;

        while ($entityName = array_shift($entityNames)) {
            $recordsCount += $this->reindexSingleEntity($entityName);
        }

        return $recordsCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSearch(Query $query)
    {
        $this->getClient();

        return array('results' => array(), 'records_count' => 0);
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
        $aliases = $this->mapper->getEntitiesListAliases();

        return !empty($aliases[$class]) ? $aliases[$class] : null;
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function getIndexData($entity)
    {
        $indexData = array();
        foreach ($this->mapper->mapObject($entity) as $fields) {
            $indexData = array_merge($indexData, $fields);
        }

        foreach ($indexData as $key => $value) {
            if ($value instanceof \DateTime) {
                $value->setTimezone(new \DateTimeZone('UTC'));
                $indexData[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $indexData[$key] = (string)$value;
            }
        }

        return $indexData;
    }
}
