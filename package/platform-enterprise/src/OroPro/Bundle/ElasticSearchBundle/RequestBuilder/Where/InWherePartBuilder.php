<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

use Oro\Bundle\SearchBundle\Query\Query;

class InWherePartBuilder extends AbstractWherePartBuilder
{
    /**
     * @var array
     */
    protected $supporterOperators = [Query::OPERATOR_IN, Query::OPERATOR_NOT_IN];

    /**
     * {@inheritdoc}
     */
    public function buildPart($field, $type, $operator, $value)
    {
        // define bool part
        $boolPart = 'should';
        if ($operator == Query::OPERATOR_NOT_IN) {
            $boolPart = 'must_not';
        }

        // value must be array
        if (!is_array($value)) {
            $value = [$value];
        }

        // build filter condition
        $condition = [];
        foreach ($value as $valueItem) {
            $condition[] = ['term' => [$field => $valueItem]];
        }

        return ['bool' => [$boolPart => $condition]];
    }
}
