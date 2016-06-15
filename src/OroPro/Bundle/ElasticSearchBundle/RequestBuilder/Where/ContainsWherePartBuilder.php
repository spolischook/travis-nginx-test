<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\Engine\IndexAgent;

class ContainsWherePartBuilder extends AbstractWherePartBuilder
{
    /**
     * @var array
     */
    protected $supporterOperators = [Query::OPERATOR_CONTAINS, Query::OPERATOR_NOT_CONTAINS];

    /**
     * {@inheritdoc}
     */
    public function buildPart($field, $type, $operator, $value)
    {
        $condition = ['match' => [sprintf('%s.%s', $field, IndexAgent::FULLTEXT_ANALYZED_FIELD) => $value]];

        if ($operator === Query::OPERATOR_NOT_CONTAINS) {
            return ['bool' => ['must_not' => $condition]];
        }

        return $condition;
    }
}
