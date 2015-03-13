<?php

namespace OroPro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\OrganizationBundle\Entity\UserOrganization;

class UserOrganizationRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param Organization $organization
     * @return null|object|UserOrganization
     */
    public function getUserOrganization(User $user, Organization $organization)
    {
        $userOrganization = $this->findOneBy(['user' => $user, 'organization' => $organization]);

        if (!$userOrganization) {
            $userOrganization = new UserOrganization($user, $organization);
            $em = $this->getEntityManager();
            $em->persist($userOrganization);
            $em->flush($userOrganization);
        }

        return $userOrganization;
    }
}
