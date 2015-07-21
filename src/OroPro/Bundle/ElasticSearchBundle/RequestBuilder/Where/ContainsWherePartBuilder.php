<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;

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
        // define bool part
        $boolPart = 'must';
        if ($operator == Query::OPERATOR_NOT_CONTAINS) {
            $boolPart = 'must_not';
        }

        // define query part
        if ($field == Indexer::TEXT_ALL_DATA_FIELD) {
            // nGram tokenizer is used
            $queryPart = ['match' => [$field => $value]];
        } else {
            // regular wildcard
            $queryPart = ['wildcard' => [$field => '*' . $value . '*']];
        }

        return ['bool' => [$boolPart => [$queryPart]]];
    }
}
