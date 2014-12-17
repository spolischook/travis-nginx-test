<?php

namespace OroPro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * This class store organization for create/edit process in System access mode
 *
 * Class OrganizationIdProvider
 *
 * @package OroPro\Bundle\OrganizationBundle\Provider
 */
class OrganizationIdProvider
{
    /** @var Organization */
    protected $organization;

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return bool|int
     */
    public function getOrganizationId()
    {
        if ($this->organization) {
            return $this->organization->getId();
        }

        return false;
    }
}
