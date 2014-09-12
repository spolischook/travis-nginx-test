<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Query;

interface RequestBuilderInterface
{
    /**
     * Build search request for ElasticSearch engine, returns modified request
     *
     * @param Query $query
     * @param array $request
     * @return array
     */
    public function build(Query $query, array $request);
}
