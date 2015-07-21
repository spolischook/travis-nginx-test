<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\SearchBundle\Query\Query;

class ElasticExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var AbstractWherePartBuilder[]
     */
    protected $partBuilders = [];

    /**
     * @param AbstractWherePartBuilder[] $partBuilders
     */
    public function __construct(array $partBuilders)
    {
        $this->partBuilders = $partBuilders;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $value = $comparison->getValue()->getValue();
        list($type, $field) = $this->getFieldInfo($comparison->getField());
        $operator = $this->getSearchOperatorByComparisonOperator($comparison->getOperator());

        foreach ($this->partBuilders as $partBuilder) {
            if ($partBuilder->isOperatorSupported($operator)) {
                return $partBuilder->buildPart($field, $type, $operator, $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return ['bool' => ['must' => $expressionList]];
            case CompositeExpression::TYPE_OR:
                return ['bool' => ['should' => $expressionList]];
            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }
    }

    /**
     * @param string $field
     *
     * @return array
     */
    protected function getFieldInfo($field)
    {
        $fieldType = Query::TYPE_TEXT;
        if (strpos($field, '.') !== false) {
            list($fieldType, $field) = explode('.', $field);
        }

        return [$fieldType, $field];
    }

    /**
     * @param string $operator
     *
     * @return string
     */
    protected function getSearchOperatorByComparisonOperator($operator)
    {
        switch ($operator) {
            case Comparison::CONTAINS:
                return Query::OPERATOR_CONTAINS;
            case Comparison::NEQ:
                return Query::OPERATOR_NOT_EQUALS;
        }

        return strtolower($operator);
    }
}
