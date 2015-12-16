<?php

namespace OroCRM\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroCRM\Bundle\MailChimpBundle\Entity\StaticSegment;
use OroCRM\Bundle\MailChimpBundle\Entity\StaticSegmentMember;
use OroCRM\Bundle\MailChimpBundle\Model\StaticSegment\MarketingListQueryBuilderAdapter;

class StaticSegmentMemberRemoveStateIterator extends AbstractStaticSegmentIterator
{
    /**
     * @param StaticSegment $staticSegment
     *
     * @return \Iterator|BufferedQueryResultIterator
     */
    protected function createSubordinateIterator($staticSegment)
    {
        if (!$this->segmentMemberClassName) {
            throw new \InvalidArgumentException('StaticSegmentMember class name must be provided');
        }
        $qb = $this
            ->getIteratorQueryBuilder($staticSegment);

        $identity = MarketingListQueryBuilderAdapter::MEMBER_ALIAS . '.id';
        $qb->select($identity)
            ->andWhere($qb->expr()->isNotNull($identity));

        $segmentMembersQb = clone $qb;
        $segmentMembersQb
            ->resetDQLParts()
            ->select(
                [
                    'staticSegment.id static_segment_id',
                    'smmb.id member_id',
                    $segmentMembersQb->expr()->literal(StaticSegmentMember::STATE_REMOVE) . ' state'
                ]
            )
            ->from($this->segmentMemberClassName, 'segmentMember')
            ->join('segmentMember.member', 'smmb')
            ->join('segmentMember.staticSegment', 'staticSegment')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('staticSegment.id', $staticSegment->getId()),
                    $segmentMembersQb->expr()->in('smmb.id', $qb->getDQL())
                )
            );

        $bufferedIterator = new BufferedQueryResultIterator($segmentMembersQb);
        $bufferedIterator->setReverse(true);
        $bufferedIterator->setBufferSize(self::BUFFER_SIZE);

        return $bufferedIterator;
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function prepareIteratorPart(QueryBuilder $qb)
    {
        if (!$this->removedItemClassName) {
            throw new \InvalidArgumentException('Removed Item Class name must be provided');
        }

        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);

        $qb
            ->leftJoin(
                $this->removedItemClassName,
                'mlr',
                Join::WITH,
                "mlr.entityId = $entityAlias.id"
            )
            ->andWhere($qb->expr()->isNotNull('mlr.id'));
    }
}
