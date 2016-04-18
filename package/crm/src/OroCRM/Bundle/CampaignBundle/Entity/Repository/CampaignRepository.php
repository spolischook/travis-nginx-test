<?php

namespace OroCRM\Bundle\CampaignBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class CampaignRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param int       $recordsCount
     * @param array     $dateRange
     * @return array
     */
    public function getCampaignsLeads(AclHelper $aclHelper, $recordsCount, $dateRange = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('campaign.name as label', 'COUNT(lead.id) as number', 'MAX(campaign.createdAt) as maxCreated')
            ->from('OroCRMCampaignBundle:Campaign', 'campaign')
            ->leftJoin('OroCRMSalesBundle:Lead', 'lead', 'WITH', 'lead.campaign = campaign')
            ->orderBy('maxCreated', 'DESC')
            ->groupBy('campaign.name')
            ->setMaxResults($recordsCount);

        if ($dateRange) {
            $qb->where($qb->expr()->between('lead.createdAt', ':dateFrom', ':dateTo'))
                ->setParameter('dateFrom', $dateRange['start'])
                ->setParameter('dateTo', $dateRange['end']);
        }

        return $aclHelper->apply($qb)->getArrayResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param int       $recordsCount
     * @param array     $dateRange
     * @return array
     */
    public function getCampaignsOpportunities(AclHelper $aclHelper, $recordsCount, $dateRange = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('campaign.name as label', 'COUNT(opportunities.id) as number')
            ->from('OroCRMCampaignBundle:Campaign', 'campaign')
            ->join('OroCRMSalesBundle:Lead', 'lead', 'WITH', 'lead.campaign = campaign')
            ->join('lead.opportunities', 'opportunities')
            ->orderBy('number', 'DESC')
            ->groupBy('campaign.name')
            ->setMaxResults($recordsCount);

        if ($dateRange) {
            $qb->where($qb->expr()->between('opportunities.createdAt', ':dateFrom', ':dateTo'))
                ->setParameter('dateFrom', $dateRange['start'])
                ->setParameter('dateTo', $dateRange['end']);
        }

        return $aclHelper->apply($qb)->getArrayResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param int       $recordsCount
     * @param array     $dateRange
     * @return array
     */
    public function getCampaignsByCloseRevenue(AclHelper $aclHelper, $recordsCount, $dateRange = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                'campaign.name as label',
                'SUM(CASE WHEN (opp.status=\'won\') THEN opp.closeRevenue ELSE 0 END) as closeRevenue'
            )
            ->from('OroCRMCampaignBundle:Campaign', 'campaign')
            ->join('OroCRMSalesBundle:Lead', 'lead', 'WITH', 'lead.campaign = campaign')
            ->join('lead.opportunities', 'opp')
            ->orderBy('closeRevenue', 'DESC')
            ->groupBy('campaign.name')
            ->setMaxResults($recordsCount);

        if ($dateRange) {
            $qb->where($qb->expr()->between('opp.createdAt', ':dateFrom', ':dateTo'))
                ->setParameter('dateFrom', $dateRange['start'])
                ->setParameter('dateTo', $dateRange['end']);
        }

        return $aclHelper->apply($qb)->getArrayResult();
    }
}
