<?php

namespace OroCRM\Bundle\SalesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class LeadRepository extends EntityRepository
{
    /**
     * Returns top $limit opportunities grouped by lead source
     *
     * @param  AclHelper $aclHelper
     * @param  int       $limit
     * @param  array     $dateRange
     *
     * @return array     [itemCount, label]
     */
    public function getOpportunitiesByLeadSource(AclHelper $aclHelper, $limit = 10, $dateRange = null, $owners = [])
    {
        $qb = $this->createQueryBuilder('l')
            ->select('s.id as source, count(o.id) as itemCount')
            ->leftJoin('l.opportunities', 'o')
            ->leftJoin('l.source', 's')
            ->groupBy('source');

        if ($dateRange && $dateRange['start'] && $dateRange['end']) {
            $qb->andWhere($qb->expr()->between('o.createdAt', ':dateStart', ':dateEnd'))
                ->setParameter('dateStart', $dateRange['start'])
                ->setParameter('dateEnd', $dateRange['end']);
        }
        if ($owners) {
            QueryUtils::applyOptimizedIn($qb, 'o.owner', $owners);
        }

        $rows = $aclHelper->apply($qb)->getArrayResult();

        return $this->processOpportunitiesByLeadSource($rows, $limit);
    }

    /**
     * @param array $rows
     * @param int   $limit
     *
     * @return array
     */
    protected function processOpportunitiesByLeadSource(array $rows, $limit)
    {
        $result       = [];
        $unclassified = null;
        $others       = [];

        $this->sortByCountReverse($rows);
        foreach ($rows as $row) {
            if ($row['itemCount']) {
                if ($row['source'] === null) {
                    $unclassified = $row;
                } else {
                    if (count($result) < $limit) {
                        $result[] = $row;
                    } else {
                        $others[] = $row;
                    }
                }
            }
        }

        if ($unclassified) {
            if (count($result) === $limit) {
                // allocate space for 'unclassified' item
                array_unshift($others, array_pop($result));
            }
            // add 'unclassified' item to the top to avoid moving it to $others
            array_unshift($result, $unclassified);
        }
        if (!empty($others)) {
            if (count($result) === $limit) {
                // allocate space for 'others' item
                array_unshift($others, array_pop($result));
            }
            // add 'others' item
            $result[] = [
                'source'    => '',
                'itemCount' => $this->sumCount($others)
            ];
        }

        return $result;
    }

    /**
     * @param array $rows
     *
     * @return int
     */
    protected function sumCount(array $rows)
    {
        $result = 0;
        foreach ($rows as $row) {
            $result += $row['itemCount'];
        }

        return $result;
    }

    /**
     * @param array $rows
     */
    protected function sortByCountReverse(array &$rows)
    {
        usort(
            $rows,
            function ($a, $b) {
                if ($a['itemCount'] === $b['itemCount']) {
                    return 0;
                }

                return $a['itemCount'] < $b['itemCount'] ? 1 : -1;
            }
        );
    }

    /**
     * @param AclHelper $aclHelper
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int[] $owners
     *
     * @return int
     */
    public function getLeadsCount(AclHelper $aclHelper, \DateTime $start = null, \DateTime $end = null, $owners = [])
    {
        $qb = $this
            ->createLeadsCountQb($start, $end, $owners)
            ->innerJoin('l.opportunities', 'o');

        return $aclHelper->apply($qb)->getSingleScalarResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int[] $owners
     *
     * @return int
     */
    public function getNewLeadsCount(AclHelper $aclHelper, \DateTime $start = null, \DateTime $end = null, $owners = [])
    {
        $qb = $this->createLeadsCountQb($start, $end, $owners);

        return $aclHelper->apply($qb)->getSingleScalarResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param int[] $owners
     *
     * @return int
     */
    public function getOpenLeadsCount(AclHelper $aclHelper, $owners = [])
    {
        $qb = $this->createLeadsCountQb(null, null, $owners);
        $qb->andWhere(
            $qb->expr()->notIn('l.status', ['qualified', 'canceled'])
        );

        return $aclHelper->apply($qb)->getSingleScalarResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int[] $owners
     *
     * @return QueryBuilder
     */
    protected function createLeadsCountQb(
        \DateTime $start = null,
        \DateTime $end = null,
        $owners = []
    ) {
        $qb = $this
            ->createQueryBuilder('l')
            ->select('COUNT(DISTINCT l.id)');

        if ($start) {
            $qb
                ->andWhere('l.createdAt > :start')
                ->setParameter('start', $start);
        }
        if ($end) {
            $qb
                ->andWhere('l.createdAt < :end')
                ->setParameter('end', $end);
        }

        if ($owners) {
            QueryUtils::applyOptimizedIn($qb, 'l.owner', $owners);
        }

        return $qb;
    }
}
