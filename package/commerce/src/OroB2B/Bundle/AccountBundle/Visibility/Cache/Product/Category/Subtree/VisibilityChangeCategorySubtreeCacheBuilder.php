<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class VisibilityChangeCategorySubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /**
     * @param Category $category
     */
    public function resolveVisibilitySettings(Category $category)
    {
        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountGroupsFirstLevel(Category $category, $visibility)
    {
        $accountGroupIdsForUpdate = $this->getAccountGroupIdsFirstLevel($category);
        if ($accountGroupIdsForUpdate === null) {
            return [];
        }

        $this->updateAccountGroupsProductVisibility($category, $accountGroupIdsForUpdate, $visibility);

        return $accountGroupIdsForUpdate;
    }

    /**
     * Get accounts groups with account visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return array
     */
    protected function getAccountGroupIdsFirstLevel(Category $category)
    {
        return $this->getAccountGroupIdsWithFallbackToAll($category);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountsFirstLevel(Category $category, $visibility)
    {
        $accountIdsForUpdate = $this->getAccountIdsFirstLevel($category);

        if ($accountIdsForUpdate === null) {
            return [];
        }

        /**
         * Cache updated account for current category into appropriate section
         */
        $this->accountIdsWithChangedVisibility[$category->getId()] = $accountIdsForUpdate;

        $this->updateAccountsProductVisibility($category, $accountIdsForUpdate, $visibility);

        return $accountIdsForUpdate;
    }

    /**
     * Get accounts with account group visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return array
     */
    protected function getAccountIdsFirstLevel(Category $category)
    {
        $accountIdsForUpdate = $this->getAccountIdsWithFallbackToAll($category);
        $accountGroupIdsForUpdate = $this->accountGroupIdsWithChangedVisibility[$category->getId()];
        if (!empty($accountGroupIdsForUpdate)) {
            $accountIdsForUpdate = array_merge(
                $accountIdsForUpdate,
                /**
                 * Get accounts with account visibility fallback to 'Account Group'
                 * for account groups with fallback 'Visibility To All'
                 * for current category
                 */
                $this->getAccountIdsForUpdate($category, $accountGroupIdsForUpdate)
            );
        }

        return $accountIdsForUpdate;
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getAccountGroupIdsWithFallbackToAll(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->createQueryBuilder();

        $qb->select('accountGroup.id')
            ->from('OroB2BAccountBundle:AccountGroup', 'accountGroup')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'accountGroupCategoryVisibility',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('accountGroupCategoryVisibility.accountGroup', 'accountGroup'),
                    $qb->expr()->eq('accountGroupCategoryVisibility.category', ':category')
                )
            )
            ->where($qb->expr()->isNull('accountGroupCategoryVisibility.id'))
            ->setParameter('category', $category);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNotNull('cv.visibility'));
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNull('cv.visibility'));
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved', 'pvr')
            ->set('pvr.visibility', $visibility)
            ->andWhere($qb->expr()->in('IDENTITY(pvr.category)', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\CategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->eq('node', 'cv.category')
        );
    }
}
