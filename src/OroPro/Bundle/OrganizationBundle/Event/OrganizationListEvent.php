<?php

namespace OroPro\Bundle\OrganizationBundle\Event;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\EventDispatcher\Event;

class OrganizationListEvent extends Event
{
    const NAME = 'oro_pro_organization.organization_list';

    /**
     * @var string
     */
    protected $route;

    /**
     * @var Collection|Organization[]
     */
    protected $organizations;

    /**
     * @param string $route
     * @param Collection $organizations
     */
    public function __construct($route, Collection $organizations)
    {
        $this->route = $route;
        $this->organizations = $organizations;
    }

    /**
     * @return Collection|Organization[]
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}
