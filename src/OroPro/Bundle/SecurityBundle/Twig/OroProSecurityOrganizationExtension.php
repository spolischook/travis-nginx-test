<?php

namespace OroPro\Bundle\SecurityBundle\Twig;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityOrganizationExtension;

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
        if ($user) {
            $userOrganizations = $user->getOrganizations(true)->toArray();
            if (!empty($userOrganizations)) {
                usort(
                    $userOrganizations,
                    function (Organization $firstOrg, Organization $secondOrg) {
                        return (int)!$firstOrg->getIsGlobal();
                    }
                );
                $hasGlobalOrg = $userOrganizations[0]->getIsGlobal();

                foreach ($userOrganizations as $org) {
                    $orgName = $org->getName();
                    if ($hasGlobalOrg && !$org->getIsGlobal()) {
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
