<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Composite primary key fields order:
 *  - account
 *  - category
 */
class AccountCategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;
    use BasicOperationRepositoryTrait;

    /**
     * @param Category $category
     * @param Account $account
     * @return int visible|hidden|config
     */
    public function getFallbackToAccountVisibility(Category $category, Account $account)
    {
        $accountGroup = $account->getGroup();

        $qb = $this->getEntityManager()->createQueryBuilder();

        $configFallback = BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb->select('COALESCE(acvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) . ')')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            );

        if ($accountGroup) {
            $qb->select('COALESCE(' .
                'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) .
            ')')
                ->leftJoin(
                    'OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                    'agcvr',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('agcvr.category', 'category'),
                        $qb->expr()->eq('agcvr.accountGroup', ':accountGroup')
                    )
                )
                ->setParameter('accountGroup', $accountGroup);
        }

        $qb
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
                'acvr',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('acvr.category', 'category'),
                    $qb->expr()->eq('acvr.account', ':account')
                )
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category)
            ->setParameter('account', $account);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Category $category
     * @param array $accountIds
     * @return array
     */
    public function getVisibilitiesForAccounts(Category $category, array $accountIds)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $configFallback = BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;

        $qb->select(
            'account.id as accountId',
            'COALESCE(' .
            'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) .
            ') as visibility'
        )
        ->from('OroB2BAccountBundle:Account', 'account')
        ->leftJoin(
            'OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
            'acvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('acvr.category', ':category'),
                $qb->expr()->eq('acvr.account', 'account')
            )
        )
        ->leftJoin(
            'OroB2BAccountBundle:AccountGroup',
            'accountGroup',
            Join::WITH,
            $qb->expr()->eq('account.group', 'accountGroup')
        )
        ->leftJoin(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('agcvr.category', ':category'),
                $qb->expr()->eq('agcvr.accountGroup', 'accountGroup')
            )
        )
        ->leftJoin(
            'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            Join::WITH,
            $qb->expr()->eq('cvr.category', ':category')
        )
        ->where($qb->expr()->in('account', ':accountIds'))
        ->setParameter('category', $category)
        ->setParameter('accountIds', $accountIds);

        $fallBackToAccountVisibilities = [];
        foreach ($qb->getQuery()->getArrayResult() as $resultItem) {
            $fallBackToAccountVisibilities[(int)$resultItem['accountId']] = (int)$resultItem['visibility'];
        }

        return $fallBackToAccountVisibilities;
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, Account $account, $configValue)
    {
        $visibility = $this->getFallbackToAccountVisibility($category, $account);
        if ($visibility === AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
            $visibility = $configValue;
        }

        return $visibility === AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param Account $account
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, Account $account, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];
        if ($account->getGroup()) {
            $terms[] = $this->getAccountGroupCategoryVisibilityResolvedTerm($qb, $account->getGroup(), $configValue);
        }
        $terms[] = $this->getAccountCategoryVisibilityResolvedTerm($qb, $account, $configValue);

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Account $account
     */
    public function updateAccountCategoryVisibilityByCategory(Account $account, array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', $visibility)
            ->where($qb->expr()->eq('acvr.account', ':account'))
            ->andWhere($qb->expr()->in('IDENTITY(acvr.category)', ':categoryIds'))
            ->setParameters(['account' => $account, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return null|AccountCategoryVisibilityResolved
     */
    public function findByPrimaryKey(Category $category, Account $account)
    {
        return $this->findOneBy(['account' => $account, 'category' => $category]);
    }

    public function clearTable()
    {
        // TRUNCATE can't be used because it can't be rolled back in case of DB error
        $this->createQueryBuilder('acvr')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function insertStaticValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN acv.visibility = '%s' THEN %s ELSE %s END",
            AccountCategoryVisibility::VISIBLE,
            AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE,
            AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.account)',
                $visibilityCondition,
                (string)AccountCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroB2BAccountBundle:Visibility\AccountCategoryVisibility', 'acv')
            ->where('acv.visibility IN (:staticVisibilities)')
            ->setParameter(
                'staticVisibilities',
                [AccountCategoryVisibility::VISIBLE, AccountCategoryVisibility::HIDDEN]
            );

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'account', 'visibility', 'source'],
            $queryBuilder
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function insertCategoryValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            'CASE WHEN cvr.visibility IS NOT NULL THEN cvr.visibility ELSE %s END',
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.account)',
                $visibilityCondition,
                (string)AccountCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroB2BAccountBundle:Visibility\AccountCategoryVisibility', 'acv')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'acv.category = cvr.category'
            )
            ->where('acv.visibility = :category')
            ->setParameter('category', AccountCategoryVisibility::CATEGORY);

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'account', 'visibility', 'source'],
            $queryBuilder
        );
    }

    /**
     * [
     *      [
     *          'visibility_id' => <int>,
     *          'parent_visibility_id' => <int|null>,
     *          'parent_visibility' => <string|null>,
     *          'category_id' => <int>,
     *          'parent_category_id' => <int|null>,
     *          'parent_group_resolved_visibility' => <int|null>,
     *          'parent_category_resolved_visibility' => <int|null>
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    public function getParentCategoryVisibilities()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select(
            'acv.id as visibility_id',
            'acv_parent.id as parent_visibility_id',
            'acv_parent.visibility as parent_visibility',
            'c.id as category_id',
            'IDENTITY(c.parentCategory) as parent_category_id',
            'agcvr_parent.visibility as parent_group_resolved_visibility',
            'cvr_parent.visibility as parent_category_resolved_visibility'
        )
        ->from('OroB2BAccountBundle:Visibility\AccountCategoryVisibility', 'acv')
        // join to category that includes only parent category entities
        ->innerJoin(
            'acv.category',
            'c',
            'WITH',
            'acv.visibility = ' . $qb->expr()->literal(AccountCategoryVisibility::PARENT_CATEGORY)
        )
        // join to related account
        ->innerJoin('OroB2BAccountBundle:Account', 'a', 'WITH', 'acv.account = a')
        // join to parent category visibility
        ->leftJoin(
            'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
            'acv_parent',
            'WITH',
            'acv_parent.account = acv.account AND acv_parent.category = c.parentCategory'
        )
        // join to resolved group visibility for parent category
        ->leftJoin(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr_parent',
            'WITH',
            'agcvr_parent.accountGroup = a.group AND agcvr_parent.category = c.parentCategory'
        )
        // join to resolved category visibility for parent category
        ->leftJoin(
            'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr_parent',
            'WITH',
            'cvr_parent.category = c.parentCategory'
        )
        // order is important to make sure that higher level categories will be processed first
        ->addOrderBy('c.level', 'ASC')
        ->addOrderBy('c.left', 'ASC')
        ->getQuery()
        ->getScalarResult();
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param array $visibilityIds
     * @param int $visibility
     */
    public function insertParentCategoryValues(
        InsertFromSelectQueryExecutor $insertExecutor,
        array $visibilityIds,
        $visibility
    ) {
        if (!$visibilityIds) {
            return;
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.account)',
                (string)$visibility,
                (string)AccountCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            )
            ->from('OroB2BAccountBundle:Visibility\AccountCategoryVisibility', 'acv')
            ->andWhere('acv.visibility = :parentCategory')  // parent category fallback
            ->andWhere('acv.id IN (:visibilityIds)')        // specific visibility entity IDs
            ->setParameter('parentCategory', AccountCategoryVisibility::PARENT_CATEGORY);

        foreach (array_chunk($visibilityIds, CategoryRepository::INSERT_BATCH_SIZE) as $ids) {
            $queryBuilder->setParameter('visibilityIds', $ids);
            $insertExecutor->execute(
                $this->getClassName(),
                ['sourceCategoryVisibility', 'category', 'account', 'visibility', 'source'],
                $queryBuilder
            );
        }
    }
}
