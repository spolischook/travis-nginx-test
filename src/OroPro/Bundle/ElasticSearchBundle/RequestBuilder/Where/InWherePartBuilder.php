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
    public function buildPart($field, $type, $operator, $value, $keyword, array $request)
    {
        // define bool part
        $boolPart = 'must';
        if ($operator == Query::OPERATOR_NOT_IN) {
            $boolPart = 'must_not';
        } elseif ($keyword == Query::KEYWORD_OR) {
            $boolPart = 'should';
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

        if ($condition) {
            $request['body']['query']['filtered']['filter']['bool'][$boolPart][] = ['or' => $condition];
        }

        return $request;
    }
}
