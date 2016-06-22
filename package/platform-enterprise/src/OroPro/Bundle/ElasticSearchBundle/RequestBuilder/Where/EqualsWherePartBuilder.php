<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

use Oro\Bundle\SearchBundle\Query\Query;

class EqualsWherePartBuilder extends AbstractWherePartBuilder
{
    /**
     * @var array
     */
    protected $supporterOperators = [Query::OPERATOR_EQUALS, Query::OPERATOR_NOT_EQUALS];

    /**
     * {@inheritdoc}
     */
    public function buildPart($field, $type, $operator, $value)
    {
        $condition = ['match' => [$field => $value]];

        if ($operator === Query::OPERATOR_NOT_EQUALS) {
            return ['bool' => ['must_not' => $condition]];
        }

        return $condition;
    }
}
