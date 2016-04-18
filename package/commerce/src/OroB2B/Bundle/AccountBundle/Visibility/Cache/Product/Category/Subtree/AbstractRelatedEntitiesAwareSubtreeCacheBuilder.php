<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractRelatedEntitiesAwareSubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /** @var array */
    protected $accountGroupIdsWithChangedVisibility = [];

    /** @var array */
    protected $accountIdsWithChangedVisibility = [];

    /**
     * @param Category $category
     * @param int $visibility
     * @return array
     */
    abstract protected function updateAccountGroupsFirstLevel(Category $category, $visibility);

    /**
     * @param Category $category
     * @param int $visibility
     * @return array
     */
    abstract protected function updateAccountsFirstLevel(Category $category, $visibility);

    protected function clearChangedEntities()
    {
        $this->accountGroupIdsWithChangedVisibility = [];
        $this->accountIdsWithChangedVisibility = [];
    }

    /**
     * @param Category $category
     * @param int $visibility
     * @param array|null $accountGroupIdsWithChangedVisibility
     * @param array|null $accountIdsWithChangedVisibility
     */
    protected function updateProductVisibilitiesForCategoryRelatedEntities(
        Category $category,
        $visibility,
        array $accountGroupIdsWithChangedVisibility = null,
        array $accountIdsWithChangedVisibility = null
    ) {
        if ($accountGroupIdsWithChangedVisibility === null) {
            $this->accountGroupIdsWithChangedVisibility[$category->getId()]
                = $this->updateAccountGroupsFirstLevel($category, $visibility);
        } else {
            $this->accountGroupIdsWithChangedVisibility[$category->getId()]
                = $accountGroupIdsWithChangedVisibility;
        }

        if ($accountIdsWithChangedVisibility === null) {
            $this->accountIdsWithChangedVisibility[$category->getId()]
                = $this->updateAccountsFirstLevel($category, $visibility);
        } else {
            $this->accountIdsWithChangedVisibility[$category->getId()]
                = $accountIdsWithChangedVisibility;
        }

        if (!$this->accountGroupIdsWithChangedVisibility[$category->getId()] &&
            !$this->accountIdsWithChangedVisibility[$category->getId()]
        ) {
            return;
        }

        $childCategories = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getAllChildCategories($category);

        $childCategoryLevels = [];
        /** @var Category[] $childCategories */
        foreach ($childCategories as $childCategory) {
            $childCategoryLevels[$childCategory->getLevel()][] = $childCategory;
        }

        $firstSubCategoryLevel = $category->getLevel() + 1;
        if (!empty($childCategoryLevels)) {
            for ($level = $firstSubCategoryLevel; $level <= max(array_keys($childCategoryLevels)); $level++) {
                $this->updateLevelCategories($childCategoryLevels[$level], $visibility);
            }
        }

        unset($childCategories);

        $childCategoriesWithFallbackToParent = $this->getDirectChildCategoriesWithFallbackToParent($category);
        foreach ($childCategoriesWithFallbackToParent as $category) {
            $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);
        }
    }

    /**
     * @param Category[] $levelCategories
     * @param int $visibility
     */
    protected function updateLevelCategories($levelCategories, $visibility)
    {
        /** @var Category $levelCategory */
        foreach ($levelCategories as $levelCategory) {
            $parentAccountGroups
                = $this->accountGroupIdsWithChangedVisibility[$levelCategory->getParentCategory()->getId()];
            $updatedAccountGroupIds = $this
                ->getCategoryAccountGroupIdsWithVisibilityFallbackToParent($levelCategory, $parentAccountGroups);

            /**
             * Cache updated account groups for current subcategory into appropriate section
             */
            $this->accountGroupIdsWithChangedVisibility[$levelCategory->getId()] = $updatedAccountGroupIds;

            if (!empty($updatedAccountGroupIds)) {
                $updatedAccountGroupIdsWithoutConfigFallback = $this
                    ->removeIdsWithConfigFallback($levelCategory, $updatedAccountGroupIds);
                $this->updateAccountGroupsProductVisibility($levelCategory, $updatedAccountGroupIds, $visibility);
                $this->updateAccountGroupsCategoryVisibility(
                    $levelCategory,
                    $updatedAccountGroupIdsWithoutConfigFallback,
                    $visibility
                );
            }

            $parentAccounts = $this->accountIdsWithChangedVisibility[$levelCategory->getParentCategory()->getId()];
            $accountIdsForUpdate = $this->getAccountIdsWithFallbackToParent($levelCategory, $parentAccounts);

            if (!empty($updatedAccountGroupIds)) {
                $accountIdsForUpdate = array_merge(
                    $accountIdsForUpdate,
                    $this->getAccountIdsForUpdate($levelCategory, $updatedAccountGroupIds)
                );
            }

            /**
             * Cache updated accounts for current subcategory into appropriate section
             */
            $this->accountIdsWithChangedVisibility[$levelCategory->getId()] = $accountIdsForUpdate;

            if (!empty($accountIdsForUpdate)) {
                $this->updateAccountsCategoryVisibility($levelCategory, $accountIdsForUpdate, $visibility);
                $this->updateAccountsProductVisibility($levelCategory, $accountIdsForUpdate, $visibility);
            }
        }
    }

    /**
     * @param Category $category
     * @param array $accountGroupIds
     * @return array
     */
    protected function removeIdsWithConfigFallback(Category $category, array $accountGroupIds)
    {
        $accountGroupsCategoryVisibilities = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getVisibilitiesForAccountGroups($category, $accountGroupIds);

        $accountGroupsWithConfigCallbackIds = [];
        foreach ($accountGroupsCategoryVisibilities as $accountGroupId => $visibility) {
            if ($visibility == BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
                $accountGroupsWithConfigCallbackIds[] = $accountGroupId;
            }
        }

        return array_diff($accountGroupIds, $accountGroupsWithConfigCallbackIds);
    }

    /**
     * @param Category $category
     * @param array $restrictedAccountGroupIds
     * @return array
     */
    protected function getCategoryAccountGroupIdsWithVisibilityFallbackToParent(
        Category $category,
        array $restrictedAccountGroupIds = null
    ) {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->getCategoryAccountGroupIdsByVisibility(
                $category,
                AccountGroupCategoryVisibility::PARENT_CATEGORY,
                $restrictedAccountGroupIds
            );
    }

    /**
     * @param Category $category
     * @param array $restrictedAccountIds
     * @return array
     */
    protected function getAccountIdsWithFallbackToParent(Category $category, array $restrictedAccountIds = null)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->getCategoryAccountIdsByVisibility(
                $category,
                AccountCategoryVisibility::PARENT_CATEGORY,
                $restrictedAccountIds
            );
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getAccountIdsWithFallbackToAll(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->getCategoryAccountIdsByVisibility($category, AccountCategoryVisibility::CATEGORY);
    }

    /**
     * @param Category $category
     * @param array $accountGroupIds
     * @return array
     */
    protected function getAccountIdsForUpdate(Category $category, array $accountGroupIds)
    {
        if (!$accountGroupIds) {
            return [];
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->createQueryBuilder();

        $qb->select('account.id')
            ->from('OroB2BAccountBundle:Account', 'account')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
                'accountCategoryVisibility',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('accountCategoryVisibility.account', 'account'),
                    $qb->expr()->eq('accountCategoryVisibility.category', ':category')
                )
            )
            ->leftJoin('account.group', 'accountGroup')
            ->where($qb->expr()->isNull('accountCategoryVisibility.id'))
            ->andWhere($qb->expr()->in('accountGroup', ':accountGroupIds'))
            ->setParameters([
                'category' => $category,
                'accountGroupIds' => $accountGroupIds
            ]);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    protected function getDirectChildCategoriesWithFallbackToParent(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilderPartial($category);

        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\CategoryVisibility',
            'categoryVisibility',
            Join::WITH,
            $qb->expr()->eq('node.id', 'categoryVisibility.category')
        )
        ->andWhere($qb->expr()->isNull('categoryVisibility.visibility'))
        ->andWhere($qb->expr()->eq('node.parentCategory', ':category'))
        ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Category $category
     * @param array $accountGroupIds
     * @param int $visibility
     */
    protected function updateAccountGroupsProductVisibility(Category $category, array $accountGroupIds, $visibility)
    {
        if (!$accountGroupIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->eq('agpvr.category', ':category'))
            ->andWhere($qb->expr()->in('agpvr.accountGroup', ':accountGroupIds'))
            ->setParameters([
                'accountGroupIds' => $accountGroupIds,
                'category' => $category
            ]);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param array $accountIds
     * @param $visibility
     */
    protected function updateAccountsProductVisibility(Category $category, array $accountIds, $visibility)
    {
        if (!$accountIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where($qb->expr()->eq('apvr.category', ':category'))
            ->andWhere($qb->expr()->in('apvr.account', ':accountIds'))
            ->setParameters([
                'accountIds' => $accountIds,
                'category' => $category
            ]);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param array $accountIds
     * @param $visibility
     */
    protected function updateAccountsCategoryVisibility(Category $category, array $accountIds, $visibility)
    {
        if (!$accountIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', $visibility)
            ->where($qb->expr()->eq('acvr.category', ':category'))
            ->andWhere($qb->expr()->in('acvr.account', ':accountIds'))
            ->setParameters([
                'accountIds' => $accountIds,
                'category' => $category
            ]);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param array $accountGroupIds
     * @param $visibility
     */
    protected function updateAccountGroupsCategoryVisibility(
        Category $category,
        array $accountGroupIds,
        $visibility
    ) {
        if (!$accountGroupIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved', 'agcvr')
            ->set('agcvr.visibility', $visibility)
            ->where($qb->expr()->eq('agcvr.category', ':category'))
            ->andWhere($qb->expr()->in('agcvr.accountGroup', ':accountGroupIds'))
            ->setParameters([
                'accountGroupIds' => $accountGroupIds,
                'category' => $category
            ]);

        $qb->getQuery()->execute();
    }
}
