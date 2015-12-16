<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class EntityWithOrganization
{
    /** @var int */
    protected $id;

    /** @var Organization */
    protected $organization;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }
}
