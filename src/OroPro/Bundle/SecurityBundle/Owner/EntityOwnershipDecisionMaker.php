<?php

namespace OroPro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker as BaseDecisionMaker;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Form\Model\Share;

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

    /**
     * @param object $domainObject
     * @return bool
     */
    protected function isSharingApplicable($domainObject)
    {
        $entityName = ClassUtils::getClass($domainObject);
        $shareScopes = $this->configProvider->hasConfig($entityName)
            ? $this->configProvider->getConfig($entityName)->get('share_scopes')
            : null;
        if (!$this->ace || !$shareScopes) {
            return false;
        }

        $sharedToScope = false;
        if ($this->ace->getSecurityIdentity() instanceof UserSecurityIdentity) {
            $sharedToScope = Share::SHARE_SCOPE_USER;
        } elseif ($this->ace->getSecurityIdentity() instanceof BusinessUnitSecurityIdentity) {
            $sharedToScope = Share::SHARE_SCOPE_BUSINESS_UNIT;
        } elseif ($this->ace->getSecurityIdentity() instanceof OrganizationSecurityIdentity) {
            $sharedToScope = Share::SHARE_SCOPE_ORGANIZATION;
        }

        return in_array($sharedToScope, $shareScopes, true);
    }
}
