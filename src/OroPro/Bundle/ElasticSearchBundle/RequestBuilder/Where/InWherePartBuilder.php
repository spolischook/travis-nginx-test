<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

use Oro\Bundle\SearchBundle\Query\Query;

class InWherePartBuilder extends AbstractWherePartBuilder
{
    /**
     * @var EqualsWherePartBuilder
     */
    protected $equalsBuilder;

    /**
     * @var array
     */
    protected $supporterOperators = [Query::OPERATOR_IN, Query::OPERATOR_NOT_IN];

    /**
     * @param EqualsWherePartBuilder $equalsBuilder
     */
    public function __construct(EqualsWherePartBuilder $equalsBuilder)
    {
        $this->equalsBuilder = $equalsBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function buildPart($field, $type, $operator, $value, $keyword, array $request)
    {
        // define nested query parameters
        if ($operator === Query::OPERATOR_IN) {
            $operator = Query::OPERATOR_EQUALS;
            $keyword  = Query::KEYWORD_OR;
        } else {
            $operator = Query::OPERATOR_NOT_EQUALS;
            $keyword  = Query::KEYWORD_AND;
        }

        // value must be array
        if (!is_array($value)) {
            $value = [$value];
        }

        // build equal conditions
        if ($this->equalsBuilder->isOperatorSupported($operator)) {
            foreach ($value as $valueItem) {
                $request = $this->equalsBuilder->buildPart($field, $type, $operator, $valueItem, $keyword, $request);
            }
        }

        return $request;
    }
}
