<?php

namespace OroPro\Bundle\SecurityBundle\Model;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

trait OrganizationTokenTrait
{
    /**
     * @param Organization $organization
     * @param array $roles
     * @return array
     */
    public function filterRolesInOrganizationContext(Organization $organization, array $roles)
    {
        $organizationId = $organization->getId();

        foreach ($roles as $key => $role) {
            $roleOrganization = $role->getOrganization();
            if ($roleOrganization) {
                $roleOrganizationId = $roleOrganization->getId();
                if ($roleOrganizationId !== $organizationId) {
                    unset($roles[$key]);
                }
            }
        }

        return $roles;
    }
}
