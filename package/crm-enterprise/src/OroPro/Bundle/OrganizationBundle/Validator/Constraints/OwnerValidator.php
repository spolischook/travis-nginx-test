<?php

namespace OroPro\Bundle\OrganizationBundle\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator as BaseValidator;

class OwnerValidator extends BaseValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getOrganization()
    {
        // in case of System access mode, we should take organization from the entity
        $organization = $this->securityFacade->getOrganization();
        if ($organization->getIsGlobal()) {
            $organization = $this->entityOwnerAccessor->getOrganization($this->object);
        }

        return $organization;
    }
}
