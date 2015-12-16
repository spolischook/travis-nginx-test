<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Query;

class LimitRequestBuilder implements RequestBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(Query $query, array $request)
    {
        $from = $query->getCriteria()->getFirstResult();

        if (null !== $from) {
            $request['body']['from'] = (int)$from;
        }

        $size = $query->getCriteria()->getMaxResults();
        if (null !== $size && $size) {
            $request['body']['size'] = (int)$size;
        }

        return $request;
    }
}
