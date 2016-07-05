<?php

namespace OroPro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;
use OroPro\Bundle\OrganizationBundle\Entity\UserOrganization;

class UserPreferredOrganizationRepository extends EntityRepository
{
    /**
     * Removes existing entry and creates new one for the user
     *
     * @param User         $user
     * @param Organization $organization
     */
    public function updatePreferredOrganization(User $user, Organization $organization)
    {
        $em = $this->getEntityManager();

        $removeQB = $em->createQueryBuilder()
            ->delete($this->getEntityName(), 'e')
            ->where('e.user = :user')
            ->setParameter('user', $user);
        $removeQB->getQuery()->execute();

        $this->savePreferredOrganization($user, $organization);
    }

    /**
     * Creates entry for user preferred organization
     *
     * @param User         $user
     * @param Organization $organization
     */
    public function savePreferredOrganization(User $user, Organization $organization)
    {
        $em = $this->getEntityManager();

        $entry = new UserPreferredOrganization($user, $organization);
        $em->persist($entry);

        $queryBuilder = $em->getRepository('OroProOrganizationBundle:UserOrganization');
        if (!$queryBuilder->findOneBy(['user' => $user, 'organization' => $organization])) {
            $em->persist(new UserOrganization($user, $organization));
        }

        $em->flush($entry);
    }
}
