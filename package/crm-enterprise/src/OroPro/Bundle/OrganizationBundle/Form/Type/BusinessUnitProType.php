<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitType;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class BusinessUnitProType extends BusinessUnitType
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOrganizationId()
    {
        /**
         * In system access mode we should check for additional organization in system access mode organization
         * provider and if it was set - set this organization as current
         */
        $organization   = $this->securityFacade->getOrganization();
        $organizationId = $organization->getId();
        if ($organization->getIsGlobal() && $this->organizationProvider->getOrganizationId()) {
            $organizationId = $this->organizationProvider->getOrganizationId();
        }

        return $organizationId;
    }
}
