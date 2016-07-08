<?php

namespace OroPro\Bundle\EwsBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;

class EwsEmailFolderRepository extends EntityRepository
{
    /**
     * @param EmailOrigin $origin
     * @param bool        $withOutdated
     *
     * @return QueryBuilder
     */
    public function getFoldersByOriginQueryBuilder(EmailOrigin $origin, $withOutdated = false)
    {
        $qb = $this->createQueryBuilder('ews_folder')
            ->innerJoin('ews_folder.folder', 'folder')
            ->where('folder.origin = :origin')
            ->setParameter('origin', $origin);
        if (!$withOutdated) {
            $qb->andWhere('folder.outdatedAt IS NULL');
        }
        $qb->orderBy('folder.synchronizedAt', Criteria::ASC);

        return $qb;
    }

    /**
     * @param EmailOrigin $origin
     * @param bool        $withOutdated
     *
     * @return EwsEmailFolder[]
     */
    public function getFoldersByOrigin(EmailOrigin $origin, $withOutdated = false)
    {
        return $this->getFoldersByOriginQueryBuilder($origin, $withOutdated)
            ->select('ews_folder, folder')
            ->getQuery()
            ->getResult();
    }
}
