<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class CategoryVisibilityRepository extends EntityRepository
{
    /**
     * @return array [['category_id' => <int>, 'category_parent_id' => <int>, 'visibility' => <string>], ...]
     */
    public function getCategoriesVisibilities()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select(
                'c.id as category_id',
                'IDENTITY(c.parentCategory) as category_parent_id',
                'categoryVisibility.visibility'
            )
            ->from('OroB2BCatalogBundle:Category', 'c')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\CategoryVisibility',
                'categoryVisibility',
                Join::WITH,
                $queryBuilder->expr()->eq('categoryVisibility.category', 'c')
            )
            ->addOrderBy('c.level', 'ASC')
            ->addOrderBy('c.left', 'ASC');

        return $queryBuilder->getQuery()->getScalarResult();
    }
}
