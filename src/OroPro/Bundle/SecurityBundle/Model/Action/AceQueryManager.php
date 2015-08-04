<?php

namespace OroPro\Bundle\SecurityBundle\Model\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Composite;

use Oro\Bundle\SecurityBundle\Exception\UnknownShareScopeException;
use Oro\Bundle\SecurityBundle\Model\Action\AceQueryManager as BaseManager;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class AceQueryManager extends BaseManager
{
    /**
     * {@inheritdoc}
     */
    protected function addExprByShareScope(QueryBuilder $qb, Composite $expr, $scope)
    {
        try {
            parent::addExprByShareScope($qb, $expr, $scope);
        } catch (UnknownShareScopeException $e) {
            if ($scope === Share::SHARE_SCOPE_ORGANIZATION) {
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
}
