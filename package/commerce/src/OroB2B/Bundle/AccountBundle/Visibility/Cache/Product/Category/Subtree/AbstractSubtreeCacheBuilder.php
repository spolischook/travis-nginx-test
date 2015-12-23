<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractSubtreeCacheBuilder
{
    /**
     * @var array
     */
    protected $excludedCategories = [];

    /**
     * @param Registry $registry
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     */
    public function __construct(Registry $registry, CategoryVisibilityResolverInterface $categoryVisibilityResolver)
    {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictStaticFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictToParentFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @param object|null $target
     * @return QueryBuilder
     */
    abstract protected function joinCategoryVisibility(QueryBuilder $qb, $target);

    /**
     * @param bool $visibility
     * @return int
     */
    protected function convertVisibility($visibility)
    {
        return $visibility
            ? BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param Category $category
     * @param $target
     * @return array
     */
    protected function getCategoryIdsForUpdate(Category $category, $target)
    {
        $categoriesWithStaticFallback = $this->getChildCategoriesWithFallbackStatic($category, $target);
        $categoryIds = $this->getChildCategoriesIdsWithFallbackToParent(
            $category,
            $categoriesWithStaticFallback,
            $target
        );

        $categoryIds[] = $category->getId();

        return $categoryIds;
    }

    /**
     * @param Category $category
     * @param object|null $target
     * @return array
     */
    protected function getChildCategoriesWithFallbackStatic(Category $category, $target)
    {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilderPartial($category);

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictStaticFallback($qb);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Category $category
     * @param array $categoriesWithStaticFallback
     * @param $target
     * @return array
     */
    protected function getChildCategoriesIdsWithFallbackToParent(
        Category $category,
        array $categoriesWithStaticFallback,
        $target
    ) {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category)
            ->select('partial node.{id}');

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictToParentFallback($qb);

        $leafIds = [];

        /**
         * Nodes with fallback different from 'toParent' and their children should be excluded
         * Also excluded final leaf of category tree
         * To optimize performance exclude nodes whose parents are already processed
         */
        foreach ($categoriesWithStaticFallback as $node) {
            $this->excludedCategories[] = $node;
            if ($this->isExcludedByParent($node)) {
                continue;
            } elseif ($node['left'] + 1 == $node['right']) {
                $leafIds[] = $node['id'];
            } else {
                $qb->andWhere(
                    $qb->expr()->not(
                        $qb->expr()->andX(
                            $qb->expr()->gte('node.level', $node['level']),
                            $qb->expr()->gte('node.left', $node['left']),
                            $qb->expr()->lte('node.right', $node['right'])
                        )
                    )
                );
            }
        }

        if (!empty($leafIds)) {
            $qb->andWhere($qb->expr()->notIn('node', ':leafIds'))
                ->setParameter('leafIds', $leafIds);
        }

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param array $node
     * @return bool
     */
    protected function isExcludedByParent(array $node)
    {
        foreach ($this->excludedCategories as $excludedCategory) {
            if ($node['level'] > $excludedCategory['level']
                && $node['left'] > $excludedCategory['left']
                && $node['right'] < $excludedCategory['right']
            ) {
                return true;
            }
        }

        return false;
    }
}
