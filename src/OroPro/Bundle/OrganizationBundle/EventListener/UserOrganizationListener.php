<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

class UserOrganizationListener
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * // todo CRM-2480
     *
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        return;
        $this->em = $eventArgs->getEntityManager();

        $collectionUpdates = $this->em->getUnitOfWork()->getScheduledCollectionUpdates();
        /** @var PersistentCollection $collection */
        foreach ($collectionUpdates as $collection) {
            $owner = $collection->getOwner();
            if ($owner instanceof User) {
                $snapshots = $collection->getSnapshot();
                foreach ($snapshots as $snapshot) {
                    if ($snapshot instanceof OrganizationInterface) {
                        $this->applyEmailUsersToOrganization($owner, $snapshot);
                    }
                }
            }
        }
    }

    /**
     * When new Organization is created it is necessary to create
     * EmailUser entities for this organization for its owner
     *
     * @param User                  $user
     * @param OrganizationInterface $organization
     */
    protected function applyEmailUsersToOrganization(User $user, OrganizationInterface $organization)
    {
        $emailUsers = $this->em->getRepository('OroEmailBundle:EmailUser')->findBy(['owner' => $user]);

        $emailsProcessed = [];
        foreach ($emailUsers as $emailUser) {
            if (!in_array($emailUser->getEmail()->getId(), $emailsProcessed)) {
                $eu = new EmailUser();
                $eu->setEmail($emailUser->getEmail());
                $eu->setFolder($emailUser->getFolder());
                $eu->setReceivedAt($emailUser->getReceivedAt());
                $eu->setSeen($emailUser->isSeen());
                $eu->setOwner($user);
                $eu->setOrganization($organization);

                $this->em->persist($eu);
                $this->em->getUnitOfWork()->computeChangeSet(
                    $this->em->getClassMetadata(ClassUtils::getClass($eu)),
                    $eu
                );

                $emailsProcessed[] = $emailUser->getEmail()->getId();
            }
        }
    }
}
