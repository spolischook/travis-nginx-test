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
    /** @var bool */
    protected $shared = false;

    /**
     * {@inheritdoc}
     */
    public function isSharedWithUser($user, $domainObject, $organization)
    {
        if (!$this->isSharingApplicable($domainObject)) {
            return false;
        }

        if (parent::isSharedWithUser($user, $domainObject, $organization)) {
            $this->shared = true;
            return true;
        }

        $tree = $this->getTreeProvider()->getTree();
        $securityIdentity = $this->ace->getSecurityIdentity();
        if ($securityIdentity instanceof OrganizationSecurityIdentity) {
            $userOrganizationIds = $tree->getUserOrganizationIds($this->getObjectId($user));
            $orgClass = 'Oro\Bundle\OrganizationBundle\Entity\Organization';
            foreach ($userOrganizationIds as $orgId) {
                if ($securityIdentity->equals(new OrganizationSecurityIdentity($orgId, $orgClass))) {
                    $this->shared = true;
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
        $shareScopes = $this->getSecurityConfigProvider()->hasConfig($entityName)
            ? $this->getSecurityConfigProvider()->getConfig($entityName)->get('share_scopes')
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

    /**
     * Return TRUE if maker decided that Object was shared
     *
     * @return bool
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * Set shared to default value FALSE
     *
     * @return mixed
     */
    public function resetShared()
    {
        $this->shared = false;
    }
}
