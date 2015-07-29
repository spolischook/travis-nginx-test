<?php

namespace OroPro\Bundle\SecurityBundle\Model\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Composite;

use Oro\Bundle\SecurityBundle\Exception\UnknownShareScopeException;
use Oro\Bundle\SecurityBundle\Model\Action\AceQueryManager as BaseManager;

class AceQueryManager extends BaseManager
{
    /**
     * {@inheritdoc}
     */
    protected function addExprByShareScope(QueryBuilder $qb, Composite $expr, $scope)
    {
        if ($scope == 'user') {
            $expr->add($qb->expr()->eq('asid.username', 'true'));
        } elseif ($scope == 'business_unit') {
            $expr->add(
                $qb->expr()->like(
                    'asid.identifier',
                    $qb->expr()->literal('Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\BusinessUnit%')
                )
            );
        } elseif ($scope == 'organization') {
            $expr->add(
                $qb->expr()->like(
                    'asid.identifier',
                    $qb->expr()->literal('Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\Organization%')
                )
            );
        } else {
            throw new UnknownShareScopeException($scope);
        }
    }
}
