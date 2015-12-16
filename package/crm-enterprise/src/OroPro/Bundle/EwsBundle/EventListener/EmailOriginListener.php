<?php
namespace OroPro\Bundle\EwsBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Email;

class EmailOriginListener
{
    /**
     * Deactivate old email on email origin table
     *
     * @param OnFlushEventArgs $event
     * @return array
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $oldEmails = [];

        /* @var $entityManager EntityManager */
        $entityManager = $event->getEntityManager();
        /* @var $unitOfWork UnitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User || $entity instanceof Email) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['email'][0])) {
                    $oldEmails[] = $changeSet['email'][0];
                }
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Email) {
                $oldEmails[] = $entity->getEmail();
            }
        }

        if ($oldEmails) {
            $queryBuilder = $entityManager->createQueryBuilder();
            $query = $queryBuilder->update('OroProEwsBundle:EwsEmailOrigin', 'o')
                ->set('o.isActive', $queryBuilder->expr()->literal(false))
                ->where($queryBuilder->expr()->in('o.userEmail', $oldEmails))
                ->getQuery();
            $query->execute();
        }

        return $oldEmails;
    }
}
