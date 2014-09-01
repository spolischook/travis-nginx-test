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
    public function reindex($class = null)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $realtime = true)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $realtime = true)
    {
        return 0;
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
}
