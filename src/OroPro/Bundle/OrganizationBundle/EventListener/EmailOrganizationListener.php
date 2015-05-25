<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
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

        if ($emailUser instanceof EmailUser
            && $emailUser->getOwner() === null
            && $emailUser->getOrganization() === null
        ) {
            $em = $eventArgs->getObjectManager();

            $origin = $emailUser->getFolder()->getOrigin();

            $qb = $em->getRepository('Oro\Bundle\UserBundle\Entity\User')
                ->createQueryBuilder('u')
                ->select('u')
                ->innerJoin('u.emailOrigins', 'o')
                ->where('o.id = :originId')
                ->setParameter('originId', $origin->getId())
                ->setMaxResults(1);

            try {
                /** @var User $user */
                $user = $qb->getQuery()->getSingleResult();
            } catch (UnexpectedResultException $e) {
                $this->logger->notice(sprintf('Could not determine owner of the origin ID: ', $origin->getId()));

                return;
            }

            $organizations = $user->getOrganizations();

            $length = count($organizations);
            for ($i = 0; $i < $length; $i++) {
                $organization = $organizations[$i];
                if (!$organization->getIsGlobal()) {
                    if ($i === 0) {
                        $emailUser->setOwner($user);
                        $emailUser->setOrganization($organization);
                    } else {
                        $eu = clone $emailUser;
                        $eu->setOwner($user);
                        $eu->setOrganization($organization);

                        $em->persist($eu);
                    }
                }
            }
        }
    }
}
