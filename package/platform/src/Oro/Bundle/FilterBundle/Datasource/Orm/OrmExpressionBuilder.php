<?php

namespace Oro\Bundle\FilterBundle\Datasource\Orm;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Expr\Coalesce;

class OrmExpressionBuilder implements ExpressionBuilderInterface
{
    /** @var Expr */
    protected $expr;

    /**
     * @param Expr $expr
     */
    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
    }

    /**
     * {@inheritdoc}
     */
    public function andX($_)
    {
        return call_user_func_array([$this->expr, 'andX'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orX($_)
    {
        return call_user_func_array([$this->expr, 'orX'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function comparison($x, $operator, $y, $withParam = false)
    {
        return new Expr\Comparison($x, $operator, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function eq($x, $y, $withParam = false)
    {
        return $this->expr->eq($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function neq($x, $y, $withParam = false)
    {
        /*
         * TODO: the correct expression cannot be used due a bud described in
         * This problem still exists in doctrine 2.5, in the case when we try equals expression
         * with IS NULL.
         * An example of DQL which fails:
         * SELECT u.id FROM OroUserBundle:User u
         * WHERE (
         *      CASE WHEN (:business_unit_id IS NOT NULL)
         *           THEN CASE
         *                  WHEN (:business_unit_id MEMBER OF u.businessUnits OR u.id IN (:data_in)) AND
         *                        u.id NOT IN (:data_not_in)
         *                  THEN true
         *                  ELSE false
         *              END
         *      ELSE
         *      CASE
         *          WHEN u.id IN (:data_in) AND u.id NOT IN (:data_not_in)
         *          THEN true
         *          ELSE false
         *      END
         * END) IS NULL
         *
         * When it uncommented you can check that all works ok, for example, on edit business unit page,
         * just try to apply 'no' filter on users grid on this page
         *
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->neq($x, $withParam ? ':' . $y : $y)
        );
        */

        return $this->expr->neq($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function lt($x, $y, $withParam = false)
    {
        return $this->expr->lt($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function lte($x, $y, $withParam = false)
    {
        return $this->expr->lte($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function gt($x, $y, $withParam = false)
    {
        return $this->expr->gt($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function gte($x, $y, $withParam = false)
    {
        return $this->expr->gte($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function not($restriction)
    {
        return $this->expr->not($restriction);
    }

    /**
     * {@inheritdoc}
     */
    public function in($x, $y, $withParam = false)
    {
        return $this->expr->in($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function notIn($x, $y, $withParam = false)
    {
        return $this->expr->notIn($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function isNull($x)
    {
        return $this->expr->isNull($x);
    }

    /**
     * {@inheritdoc}
     */
    public function isNotNull($x)
    {
        return $this->expr->isNotNull($x);
    }

    /**
     * {@inheritdoc}
     */
    public function like($x, $y, $withParam = false)
    {
        return $this->expr->like($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function notLike($x, $y, $withParam = false)
    {
        /*
         * TODO: the correct expression cannot be used due a workaround
         * This problem still exists in doctrine 2.5, in the case when we try equals expression
         * with IS NULL. See neq method.
         *
         * Also we cannot use NOT (x LIKE y) due a bug in AclHelper, so we have to use NOT LIKE operator.
         * Here is the error: Notice: Undefined property: Doctrine\ORM\Query\AST\ConditionalFactor::$conditionalTerms
         *      in C:\www\home\oro\crm-dev\src\Oro\src\Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper.php on line 119
         * The problem can be reproduced, for example, on System > Data Audit grid, just try to apply
         * 'does not contain' filer to 'author' column
         * Make sure that NOT (x LIKE y) works before use it; otherwise, use NOT LIKE
         *
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->not(
                $this->expr->like($x, $withParam ? ':' . $y : $y)
            )
        );
        */

        return new Expr\Comparison($x, 'NOT LIKE', $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function literal($literal)
    {
        return $this->expr->literal($literal);
    }

    /**
     * {@inheritdoc}
     */
    public function trim($x)
    {
        return $this->expr->trim($x);
    }

    /**
     * {@inheritdoc}
     */
    public function coalesce(array $x)
    {
        return new Coalesce($x);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($x)
    {
        return $this->expr->exists($x);
    }
}
