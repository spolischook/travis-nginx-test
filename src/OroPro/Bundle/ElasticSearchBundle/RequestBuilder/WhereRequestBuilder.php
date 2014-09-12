<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\AbstractWherePartBuilder;

class WhereRequestBuilder implements RequestBuilderInterface
{
    /**
     * @var AbstractWherePartBuilder[]
     */
    protected $partBuilders = [];

    /**
     * {@inheritdoc}
     */
    public function build(Query $query, array $request)
    {
        foreach ($query->getOptions() as $option) {
            $field    = $option['fieldName'];
            $type     = $option['fieldType'];
            $operator = $option['condition'];
            $value    = $option['fieldValue'];
            $keyword  = $option['type'];

            foreach ($this->partBuilders as $partBuilder) {
                if ($partBuilder->isOperatorSupported($operator)) {
                    $request = $partBuilder->buildPart($field, $type, $operator, $value, $keyword, $request);
                }
            }
        }

        return $request;
    }

    /**
     * @param AbstractWherePartBuilder $partBuilder
     */
    public function addPartBuilder(AbstractWherePartBuilder $partBuilder)
    {
        $this->partBuilders[] = $partBuilder;
    }
}
