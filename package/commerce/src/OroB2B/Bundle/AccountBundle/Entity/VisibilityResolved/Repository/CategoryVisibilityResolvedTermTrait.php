<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;

trait CategoryVisibilityResolvedTermTrait
{
    /**
     * @param QueryBuilder $qb
     * @param int $configValue
     * @return string
     */
    protected function getCategoryVisibilityResolvedTerm(QueryBuilder $qb, $configValue)
    {
        $qb->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            Join::WITH,
            $qb->expr()->eq($this->getRootAlias($qb), 'cvr.category')
        );

        return $this->formatConfigFallback('cvr.visibility', $configValue);
    }

    /**
     * @param QueryBuilder $qb
     * @param AccountGroup $account
     * @param int
     * @return string
     */
    protected function getAccountGroupCategoryVisibilityResolvedTerm(
        QueryBuilder $qb,
        AccountGroup $account,
        $configValue
    ) {
        $qb->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq($this->getRootAlias($qb), 'agcvr.category'),
                $qb->expr()->eq('agcvr.accountGroup', ':account_group')
            )
        );

        $qb->setParameter('account_group', $account);

        return sprintf(
            'COALESCE(CASE WHEN agcvr.visibility = %s THEN %s ELSE agcvr.visibility END, 0) * 10',
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $configValue
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param Account $account
     * @param int $configValue
     * @return string
     */
    protected function getAccountCategoryVisibilityResolvedTerm(QueryBuilder $qb, Account $account, $configValue)
    {
        $qb->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved',
            'acvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq($this->getRootAlias($qb), 'acvr.category'),
                $qb->expr()->eq('acvr.account', ':account')
            )
        );

        $qb->setParameter('account', $account);

        return sprintf(
            'COALESCE(CASE WHEN acvr.visibility = %s THEN %s ELSE acvr.visibility END, 0) * 100',
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $configValue
        );
    }

    /**
     * @param QueryBuilder $qb
     * @return mixed
     */
    protected function getRootAlias(QueryBuilder $qb)
    {
        return $qb->getRootAliases()[0];
    }

    /**
     * @param string $select
     * @param int $configValue
     * @return string
     */
    protected function formatConfigFallback($select, $configValue)
    {
        // wrap into COALESCE in case of multiple fields
        if (strpos($select, ',') !== false) {
            $select = sprintf('COALESCE(%s)', $select);
        }

        return sprintf(
            'CASE WHEN %1$s IS NOT NULL AND %1$s != %2$s THEN %1$s ELSE %3$s END',
            $select,
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $configValue
        );
    }
}
