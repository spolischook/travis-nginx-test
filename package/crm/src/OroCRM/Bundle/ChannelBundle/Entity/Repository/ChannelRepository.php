<?php

namespace OroCRM\Bundle\ChannelBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroCRM\Bundle\ChannelBundle\Entity\Channel;

class ChannelRepository extends EntityRepository
{
    /**
     * Returns channel names indexed by id
     *
     * @param AclHelper $aclHelper
     * @param           $type
     *
     * @return array
     */
    public function getAvailableChannelNames(AclHelper $aclHelper, $type = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('c.id', 'c.name');
        $qb->from('OroCRMChannelBundle:Channel', 'c', 'c.id')
            ->where($qb->expr()->eq('c.status', ':status'))
            ->setParameter('status', Channel::STATUS_ACTIVE);

        if (null !== $type) {
            $qb->andWhere($qb->expr()->eq('c.channelType', ':type'));
            $qb->setParameter('type', $type);
        }

        return $aclHelper->apply($qb)->getArrayResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param AclHelper $aclHelper
     * @param string    $type
     * @return integer
     */
    public function getVisitsCountByPeriodForChannelType(
        \DateTime $start,
        \DateTime $end,
        AclHelper $aclHelper,
        $type
    ) {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('COUNT(visit.id)')
            ->from('OroTrackingBundle:TrackingVisit', 'visit')
            ->join('visit.trackingWebsite', 'site')
            ->leftJoin('site.channel', 'channel')
            ->where($qb->expr()->orX(
                $qb->expr()->isNull('channel.id'),
                $qb->expr()->andX(
                    $qb->expr()->eq('channel.channelType', ':type'),
                    $qb->expr()->eq('channel.status', ':status')
                )
            ))
            ->andWhere($qb->expr()->between('visit.firstActionTime', ':dateStart', ':dateEnd'))
            ->setParameter('type', $type)
            ->setParameter('dateStart', $start)
            ->setParameter('dateEnd', $end)
            ->setParameter('status', Channel::STATUS_ACTIVE);

        return (int) $aclHelper->apply($qb)->getSingleScalarResult();
    }
}
