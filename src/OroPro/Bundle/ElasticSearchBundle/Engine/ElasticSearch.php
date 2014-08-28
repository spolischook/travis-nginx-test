<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Engine\AbstractEngine;

use Symfony\Component\EventDispatcher\EventDispatcher;

class ElasticSearch extends AbstractEngine
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $elasticSearchClient;
    /**
     * {@inheritdoc}
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcher $dispatcher,
        DoctrineHelper $doctrineHelper,
        ElasticSearchMapper $mapper
    ) {
        $this->registry            = $registry;
        $this->dispatcher          = $dispatcher;
        $this->doctrineHelper      = $doctrineHelper;
        $this->mapper              = $mapper;
        $this->elasticSearchClient = $this->initializeIndex();
    }

    /**
     * Initialized elastic search index configuration
     *
     * @returns \Elasticsearch\Client
     */
    protected function initializeIndex()
    {
        $indexParameters     = $this->mapper->getIndexConfiguration();
        $elasticSearchClient = new \Elasticsearch\Client();

        if (!$elasticSearchClient->indices()->exists(array('index' => $indexParameters['index']))) {
            $elasticSearchClient->indices()->create($indexParameters);
        }

        return $elasticSearchClient;
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
        return array();
    }
}
