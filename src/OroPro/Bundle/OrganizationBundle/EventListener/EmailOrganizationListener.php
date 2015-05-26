<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\UnexpectedResultException;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Entity\User;

class EmailOrganizationListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $emailUser = $eventArgs->getObject();

        if ($emailUser instanceof EmailUser) {
            $em = $eventArgs->getObjectManager();
            $user = $this->getEmailOwner($emailUser, $em);

            if ($user == null) {
                $this->logger->notice(sprintf(
                    'Could not determine owner of the origin ID: ',
                    $emailUser->getFolder()->getOrigin()->getId()
                ));

                return;
            }

            if ($emailUser->getOwner() == null) {
                $emailUser->setOwner($user);
            }

            $organizations = $user->getOrganizations();
            if ($emailUser->getOrganization() != null) {
                if ($organizations->contains($emailUser->getOrganization())) {
                    $organizations->removeElement($emailUser->getOrganization());
                }
            } else {
                $organization = null;
                $organizations->map(function($entry) use (&$organization) {
                    if ($organization == null) {
                        if (!$entry->getIsGlobal()) {
                            $organization = $entry;
                        }
                    }
                });
                if ($organization != null) {
                    $emailUser->setOrganization($organization);
                    $organizations->removeElement($organization);
                }
            }

            $length = count($organizations);
            for ($i = 0; $i < $length; $i++) {
                $organization = $organizations[$i];
                if (!$organization->getIsGlobal()) {
                    $eu = clone $emailUser;
                    $eu->setOwner($user);
                    $eu->setOrganization($organization);

                    $em->persist($eu);
                }
            }
        }
    }

    /**
     * @param EmailUser     $emailUser
     * @param ObjectManager $em
     *
     * @return User|null
     */
    protected function getEmailOwner(EmailUser $emailUser, ObjectManager $em)
    {
        if ($emailUser->getOwner() != null) {
            return $emailUser->getOwner();
        }

        $origin = $emailUser->getFolder()->getOrigin();

        try {
            $qb = $em->getRepository('Oro\Bundle\UserBundle\Entity\User')
                ->createQueryBuilder('u')
                ->select('u')
                ->innerJoin('u.emailOrigins', 'o')
                ->where('o.id = :originId')
                ->setParameter('originId', $origin->getId())
                ->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $this->logger->notice($e->getMessage());

            return null;
        }
    }
}
