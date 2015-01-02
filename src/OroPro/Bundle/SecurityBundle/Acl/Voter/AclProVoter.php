<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;

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

        return parent::checkOrganizationContext($result);
    }
}
