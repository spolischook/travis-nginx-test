<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - priceList
 *  - website
 */
class PriceListToAccountGroupRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param BasePriceList $priceList
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListToAccountGroup
     */
    public function findByPrimaryKey(BasePriceList $priceList, AccountGroup $accountGroup, Website $website)
    {
        return $this->findOneBy(['accountGroup' => $accountGroup, 'priceList' => $priceList, 'website' => $website]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceLists($accountGroup, Website $website, $sortOrder = Criteria::DESC)
    {
        return $this->createQueryBuilder('PriceListToAccountGroup')
            ->innerJoin('PriceListToAccountGroup.priceList', 'priceList')
            ->innerJoin('PriceListToAccountGroup.accountGroup', 'accountGroup')
            ->where('accountGroup = :accountGroup')
            ->andWhere('PriceListToAccountGroup.website = :website')
            ->orderBy('PriceListToAccountGroup.priority', $sortOrder)
            ->setParameters(['accountGroup' => $accountGroup, 'website' => $website])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Website $website
     * @param int $fallback
     * @return BufferedQueryResultIterator|AccountGroup[]
     */
    public function getAccountGroupIteratorByDefaultFallback(Website $website, $fallback)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct accountGroup')
            ->from('OroB2BAccountBundle:AccountGroup', 'accountGroup');

        $qb->innerJoin(
            'OroB2BPricingBundle:PriceListToAccountGroup',
            'plToAccountGroup',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('plToAccountGroup.accountGroup', 'accountGroup'),
                $qb->expr()->eq('plToAccountGroup.website', ':website')
            )
        );

        $qb->leftJoin(
            'OroB2BPricingBundle:PriceListAccountGroupFallback',
            'priceListFallBack',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('priceListFallBack.accountGroup', 'accountGroup'),
                $qb->expr()->eq('priceListFallBack.website', ':website')
            )
        )
        ->where(
            $qb->expr()->orX(
                $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToWebsite'),
                $qb->expr()->isNull('priceListFallBack.fallback')
            )
        )
        ->setParameter('website', $website)
        ->setParameter('fallbackToWebsite', $fallback);

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param AccountGroup $accountGroup
     * @return int[]
     */
    public function getWebsiteIdsByAccountGroup(AccountGroup $accountGroup)
    {
        $qb = $this->createQueryBuilder('PriceListToAccountGroup');

        $result = $qb->select('distinct(PriceListToAccountGroup.website)')
            ->andWhere($qb->expr()->eq('PriceListToAccountGroup.accountGroup', ':accountGroup'))
            ->setParameter('accountGroup', $accountGroup)
            ->getQuery()
            ->getResult();

        return array_map('current', $result);
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return mixed
     */
    public function delete(AccountGroup $accountGroup, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToAccountGroup')
            ->where('PriceListToAccountGroup.accountGroup = :accountGroup')
            ->andWhere('PriceListToAccountGroup.website = :website')
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }
}
