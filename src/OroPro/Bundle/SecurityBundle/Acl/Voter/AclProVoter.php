<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;

use OroPro\Bundle\SecurityBundle\Acl\Extension\AceShareDecisionInterface;

class AclProVoter extends AclVoter
{
    /**
     * {@inheritdoc}
     */
    protected function checkOrganizationContext($result)
    {
        // in system access mode we should not check entity organization
        if ($this->getSecurityToken()->getOrganizationContext()->getIsGlobal()) {
            return $result;
        }

        if ($this->getAclExtension() instanceof AceShareDecisionInterface
            && $this->getAclExtension()->isEntityShared()
        ) {
            return $result;
        }

        return parent::checkOrganizationContext($result);
    }
}
