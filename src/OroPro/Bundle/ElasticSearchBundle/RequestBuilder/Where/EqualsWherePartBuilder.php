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
        // define bool part
        $boolPart = 'must';
        if ($operator == Query::OPERATOR_NOT_EQUALS) {
            $boolPart = 'must_not';
        }

        return ['bool' => [$boolPart => ['match' => [$field => $value]]]];
    }
}
