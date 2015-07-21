<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Query;

class OrderRequestBuilder implements RequestBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(Query $query, array $request)
    {
        $query->getCriteria()->getOrderings();
        /**
         * TODO: ordering from criteria
         */
//        $field = $query->getOrderBy();
//        if ($field) {
//            $request['body']['sort'][$field]['order'] = $query->getOrderDirection();
//        }

        return $request;
    }
}
