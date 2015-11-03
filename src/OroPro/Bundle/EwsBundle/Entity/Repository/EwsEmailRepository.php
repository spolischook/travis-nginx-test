<?php

namespace OroPro\Bundle\EwsBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;

class EwsEmailRepository extends EntityRepository
{
    /**
     * @param EmailFolder $folder
     * @param string[]    $ewsIds
     *
     * @return QueryBuilder
     */
    public function getEmailsByEwsIdsQueryBuilder(EmailFolder $folder, array $ewsIds)
    {
        $qb = $this->createQueryBuilder('ews_email');
        return $qb
            ->innerJoin('ews_email.email', 'email')
            ->innerJoin('email.emailUsers', 'email_users')
            ->innerJoin('email_users.folders', 'folders')
            ->andWhere($qb->expr()->in('folders', ':folder'))
            ->andWhere('ews_email.ewsId IN (:ewsIds)')
            ->setParameter('folder', $folder)
            ->setParameter('ewsIds', $ewsIds);
    }

    /**
     * @param EmailFolder $folder
     * @param string[]    $ewsIds
     *
     * @return string[] Existing EWS ids
     */
    public function getExistingEwsIds(EmailFolder $folder, array $ewsIds)
    {
        $rows = $this->getEmailsByEwsIdsQueryBuilder($folder, $ewsIds)
            ->select('ews_email.ewsId')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['ewsId'];
        }

        return $result;
    }

    /**
     * @param EmailOrigin $origin
     * @param string[]    $messageIds
     *
     * @return QueryBuilder
     */
    public function getEmailsByMessageIdsQueryBuilder(EmailOrigin $origin, array $messageIds)
    {
        return $this->createQueryBuilder('ews_email')
            ->innerJoin('ews_email.ewsFolder', 'ews_folder')
            ->innerJoin('ews_email.email', 'email')
            ->innerJoin('email.emailUsers', 'email_users')
            ->where('email_users.origin = :origin AND email.messageId IN (:messageIds)')
            ->setParameter('origin', $origin)
            ->setParameter('messageIds', $messageIds);
    }

    /**
     * @param EmailOrigin $origin
     * @param string[]    $messageIds
     *
     * @return EwsEmail[] Existing emails
     */
    public function getEmailsByMessageIds(EmailOrigin $origin, array $messageIds)
    {
        $rows = $this->getEmailsByMessageIdsQueryBuilder($origin, $messageIds)
            ->select('ews_email, email, email_users, ews_folder')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}
