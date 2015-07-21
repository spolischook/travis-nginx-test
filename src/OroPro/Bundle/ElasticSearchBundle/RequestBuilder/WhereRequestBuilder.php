<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder;

use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\AbstractWherePartBuilder;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\ElasticExpressionVisitor;

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
        $visitor = new ElasticExpressionVisitor($this->partBuilders);

        $request['body']['query']['filtered']['query'] = $visitor->dispatch(
            $query->getCriteria()->getWhereExpression()
        );

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
