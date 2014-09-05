<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Query;

class FromRequestBuilder implements RequestBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(Query $query, array $request)
    {
        $entities = $query->getFrom();

        // if select from any entity then no type restriction needed
        if (in_array('*', $entities)) {
            return $request;
        }

        // entity aliases used as types
        if ($entities) {
            $request['type'] = implode(',', $entities);
        }

        return $request;
    }
}
