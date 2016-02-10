<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountGroupCategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param AccountGroup $accountGroup
     * @param Category $category
     * @return string|null
     */
    public function getAccountGroupCategoryVisibility(AccountGroup $accountGroup, Category $category)
    {
        $result = $this->createQueryBuilder('accountGroupCategoryVisibility')
            ->select('accountGroupCategoryVisibility.visibility')
            ->andWhere('accountGroupCategoryVisibility.accountGroup = :accountGroup')
            ->andWhere('accountGroupCategoryVisibility.category = :category')
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('category', $category)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result) {
            return $result['visibility'];
        } else {
            return null;
        }
    }
}
