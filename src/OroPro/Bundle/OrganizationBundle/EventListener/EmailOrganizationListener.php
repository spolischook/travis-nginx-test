<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnexpectedResultException;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Event\EmailUserAdded;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Entity\User;

class EmailOrganizationListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * @param EmailUserAdded $eventArgs
     */
    public function onEmailUserAdded(EmailUserAdded $eventArgs)
    {
        $emailUser = $eventArgs->getEmailUser();
        $user = $this->getEmailOwner($emailUser);

        if ($user === null) {
            $this->logger->notice(sprintf(
                'Could not determine owner of the origin ID: ',
                $emailUser->getFolder()->getOrigin()->getId()
            ));

            return;
        }

        if ($emailUser->getOwner() === null) {
            $emailUser->setOwner($user);
        }

        $organizations = $user->getOrganizations();
        if ($emailUser->getOrganization() !== null) {
            if ($organizations->contains($emailUser->getOrganization())) {
                $organizations->removeElement($emailUser->getOrganization());
            }
        } else {
            $organization = null;
            $organizations->map(function ($entry) use (&$organization) {
                if ($organization === null) {
                    if (!$entry->getIsGlobal()) {
                        $organization = $entry;
                    }
                }
            });
            if ($organization !== null) {
                $emailUser->setOrganization($organization);
                $organizations->removeElement($organization);
            }
        }

        foreach ($organizations as $organization) {
            if (!$organization->getIsGlobal()) {
                $eu = clone $emailUser;
                $eu->setOwner($user);
                $eu->setOrganization($organization);

                $this->em->persist($eu);
            }
        }
    }

    /**
     * @param EmailUser $emailUser
     *
     * @return User|null
     */
    protected function getEmailOwner(EmailUser $emailUser)
    {
        if ($emailUser->getOwner() !== null) {
            return $emailUser->getOwner();
        }

        $origin = $emailUser->getFolder()->getOrigin();

        try {
            $qb = $this->em->getRepository('Oro\Bundle\UserBundle\Entity\User')
                ->createQueryBuilder('u')
                ->select('u')
                ->innerJoin('u.emailOrigins', 'o')
                ->where('o.id = :originId')
                ->setParameter('originId', $origin->getId())
                ->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        } catch (UnexpectedResultException $e) {
            $this->logger->notice($e->getMessage());

            return null;
        }
    }
}
