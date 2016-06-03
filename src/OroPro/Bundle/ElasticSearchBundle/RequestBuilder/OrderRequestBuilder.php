<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class OrderRequestBuilder implements RequestBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(Query $query, array $request)
    {
        $orders = $query->getCriteria()->getOrderings();
        if (!empty($orders)) {
            foreach ($orders as $field => $direction) {
                $field = strtolower(Criteria::explodeFieldTypeName($field)[1]);
                $request['body']['sort'][$field]['order'] = strtolower($direction);
            }
        }

        return $request;
    }
}
