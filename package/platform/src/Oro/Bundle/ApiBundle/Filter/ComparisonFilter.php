<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

class ComparisonFilter extends StandaloneFilter
{
    const EQ  = '=';
    const NEQ = '<>';
    const LT  = '<';
    const LTE = '<=';
    const GT  = '>';
    const GTE = '>=';

    /** @var string */
    protected $field;

    /**
     * Gets a field by which data should be filtered
     *
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets a field by which data should be filtered
     *
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function isArrayAllowed($operator = null)
    {
        return
            parent::isArrayAllowed($operator)
            && in_array(null !== $operator ? $operator : self::EQ, [self::EQ, self::NEQ], true);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null)
    {
        $expr = $this->createExpression($value);
        if (null !== $expr) {
            $criteria->andWhere($expr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createExpression(FilterValue $value = null)
    {
        if (null !== $value) {
            $operator = $value->getOperator();
            $val      = $value->getValue();
        } else {
            $operator = null;
            $val      = $this->getDefaultValue();
            if (null !== $val) {
                $operator = self::EQ;
            }
        }

        return null !== $operator
            ? $this->buildExpression($this->field, $operator, $val)
            : null;
    }

    /**
     * Creates the Expression object that can be used to filter data using the Criteria object.
     *
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     *
     * @return Expression
     *
     * @throws \InvalidArgumentException
     */
    protected function buildExpression($field, $operator, $value)
    {
        if (!$field) {
            throw new \InvalidArgumentException('Field must not be empty.');
        }
        if (null === $value) {
            throw new \InvalidArgumentException(
                sprintf('Value must not be NULL. Field: "%s".', $field)
            );
        }

        switch ($operator) {
            case self::EQ:
                return is_array($value)
                    ? Criteria::expr()->in($field, $value)
                    : Criteria::expr()->eq($field, $value);
            case self::NEQ:
                return is_array($value)
                    ? Criteria::expr()->notIn($field, $value)
                    : Criteria::expr()->neq($field, $value);
            case self::GT:
                return Criteria::expr()->gt($field, $value);
            case self::LT:
                return Criteria::expr()->lt($field, $value);
            case self::GTE:
                return Criteria::expr()->gte($field, $value);
            case self::LTE:
                return Criteria::expr()->lte($field, $value);
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported operator: "%s". Field: "%s".', $operator, $field)
        );
    }
}
