<?php

namespace OroB2B\Bundle\CatalogBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @method CategoryRepository persistAsFirstChildOf() persistAsFirstChildOf(Category $node, Category $parent)
 * @method CategoryRepository persistAsNextSiblingOf() persistAsNextSiblingOf(Category $node, Category $sibling)
 */
class CategoryRepository extends NestedTreeRepository
{
    /**
     * @return Category
     */
    public function getMasterCatalogRoot()
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.parentCategory IS NULL')
            ->orderBy('category.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param object|null $node
     * @param bool $direct
     * @param string|null $sortByField
     * @param string $direction
     * @param bool $includeNode
     * @return Category[]
     */
    public function getChildrenWithTitles(
        $node = null,
        $direct = false,
        $sortByField = null,
        $direction = 'ASC',
        $includeNode = false
    ) {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)
            ->addSelect('title')
            ->leftJoin('node.titles', 'title')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Category $category
     * @return array
     */
    public function getChildrenIds(Category $category)
    {
        $result = $this->childrenQueryBuilder($category)
            ->select('node.id')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @param string $title
     * @return Category|null
     */
    public function findOneByDefaultTitle($title)
    {
        $qb = $this->createQueryBuilder('category');

        return $qb
            ->select('partial category.{id}')
            ->innerJoin('category.titles', 'title', Join::WITH, $qb->expr()->isNull('title.locale'))
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Product $product
     *
     * @return Category|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProduct(Product $product)
    {
        return $this->createQueryBuilder('category')
            ->where(':product MEMBER OF category.products')
            ->setParameter('product', $product)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $productSku
     *
     * @param bool $includeTitles
     * @return null|Category
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProductSku($productSku, $includeTitles = false)
    {
        $qb = $this->createQueryBuilder('category');

        if ($includeTitles) {
            $qb->addSelect('title.string');
            $qb->leftJoin('category.titles', 'title', Join::WITH, $qb->expr()->isNull('title.locale'));
        }

        return $qb
            ->select('partial category.{id}')
            ->innerJoin('category.products', 'p', Join::WITH, $qb->expr()->eq('p.sku', ':sku'))
            ->setParameter('sku', $productSku)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
