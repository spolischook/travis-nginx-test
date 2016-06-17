<?php

namespace OroCRMPro\Bundle\ChannelBundle\EventListener;

use OroPro\Bundle\OrganizationBundle\Event\OrganizationUpdateEvent;

use OroCRM\Bundle\ChannelBundle\Provider\StateProvider;

class OrganizationListener
{
    /** @var StateProvider */
    protected $stateProvider;

    /**
     * @param StateProvider $stateProvider
     */
    public function __construct(StateProvider $stateProvider)
    {
        $this->stateProvider = $stateProvider;
    }

    /**
     * @param OrganizationUpdateEvent $event
     */
    public function onUpdateOrganization(OrganizationUpdateEvent $event)
    {
        $organization = $event->getOrganization();

        $this->stateProvider->clearOrganizationCache($organization->getId());
    }
}
