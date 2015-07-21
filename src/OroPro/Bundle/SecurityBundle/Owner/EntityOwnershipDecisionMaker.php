<?php

namespace OroPro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker as BaseDecisionMaker;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class EntityOwnershipDecisionMaker extends BaseDecisionMaker
{
    /**
     * {@inheritdoc}
     */
    public function isSharedWithUser($user, $domainObject, $organization)
    {
        if (!$this->isSharingApplicable($domainObject)) {
            return false;
        }

        if (parent::isSharedWithUser($user, $domainObject, $organization)) {
            return true;
        }

        $tree = $this->treeProvider->getTree();
        $securityIdentity = $this->ace->getSecurityIdentity();
        if ($securityIdentity instanceof OrganizationSecurityIdentity) {
            $userOrganizationIds = $tree->getUserOrganizationIds($this->getObjectId($user));
            $orgClass = 'Oro\Bundle\OrganizationBundle\Entity\Organization';
            foreach ($userOrganizationIds as $orgId) {
                if ($securityIdentity->equals(new OrganizationSecurityIdentity($orgId, $orgClass))) {
                    return true;
                }
            }
        }

        return false;
    }
}
