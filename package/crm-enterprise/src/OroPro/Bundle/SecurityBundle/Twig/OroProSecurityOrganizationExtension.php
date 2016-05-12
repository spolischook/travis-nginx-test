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
                usort(
                    $userOrganizations,
                    function (Organization $firstOrg, Organization $secondOrg) {
                        /**
                         *  Change return code to fixed issue with changes in usort algorithm in php7
                         *  https://bugs.php.net/bug.php?id=69158
                         */
                        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
                            return (int)$secondOrg->getIsGlobal();
                        }
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
