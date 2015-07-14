<?php

namespace OroPro\Bundle\UserBundle\Helper;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserProHelper
{
    /**
     * @param User $user
     * @return bool
     */
    public function isUserAssignedToSystemOrganization(User $user)
    {
        $organizations = $user->getOrganizations();
        $isAssigned = $organizations->exists(
            function ($key, Organization $organization) {
                return $organization->getIsGlobal();
            }
        );

        return $isAssigned;
    }

    /**
     * @param User $user
     * @param Organization $org
     * @return bool
     */
    public function isUserAssignedToOrganization(User $user, Organization $org)
    {
        $organizations = $user->getOrganizations();
        $isAssigned = $organizations->exists(
            function($key, $organization) use ($org) {
                /** @var Organization $organization */
                return ($organization->getId() === $org->getId());
            }
        );

        return $isAssigned;
    }
}
