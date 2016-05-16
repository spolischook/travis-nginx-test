<?php

namespace OroPro\Bundle\SecurityBundle\Twig;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityOrganizationExtension;
use Oro\Bundle\UserBundle\Entity\User;

class OroProSecurityOrganizationExtension extends OroSecurityOrganizationExtension
{
    /**
     * {@inheritdoc}
     * We should set global organization first at the list and should add white spaces for non global organizations
     */
    public function getOrganizations()
    {
        $result = [];
        $token = $this->securityContext->getToken();
        $user = $token ? $token->getUser() : null;
        if (is_object($user) && $user instanceof User) {
            $userOrganizations = $user->getOrganizations(true)->toArray();
            if (!empty($userOrganizations)) {
                $globalOrganization = false;
                $organizationsWithoutGlobal = array_filter(
                    $userOrganizations,
                    function (Organization $organization) use (&$globalOrganization) {
                        if ($organization->getIsGlobal() === true) {
                            $globalOrganization = $organization;
                            return false;
                        }
                        return true;
                    }
                );

                $userOrganizations = $globalOrganization ?
                    array_merge([$globalOrganization], $organizationsWithoutGlobal) :
                    $organizationsWithoutGlobal;

                foreach ($userOrganizations as $org) {
                    $orgName = $org->getName();
                    if ($globalOrganization && !$org->getIsGlobal()) {
                        $orgName = '&nbsp;&nbsp;&nbsp;' . $orgName;
                    }
                    $result[] = [
                        'id' => $org->getId(),
                        'name' => $orgName
                    ];
                }
            }
        }

        return $result;
    }
}
