<?php

namespace OroPro\Bundle\OrganizationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationUpdateEvent extends Event
{
    const NAME = 'oropro_organization.organization.update';

    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @param Organization $organization
     */
    public function __construct(Organization $organization)
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
}
