<?php

namespace OroPro\Bundle\ElasticSearch\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Engine\AbstractEngine;

class ElasticSearch extends AbstractEngine
{
    /**
     * Reload search index
     *
     * @return int Count of index records
     */
    public function reindex()
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
