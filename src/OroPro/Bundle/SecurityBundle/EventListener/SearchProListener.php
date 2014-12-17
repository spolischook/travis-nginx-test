<?php

namespace OroPro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class SearchProListener extends SearchListener
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
    public function beforeSearchEvent(BeforeSearchEvent $event)
    {
        $organization = $this->securityFacade->getOrganization();
        if ($organization && $organization->getIsGlobal()) {
            // in System access mode we must check organization id in the organization Provider and if
            // it is not null - use it to limit search data
            if ($this->organizationProvider->getOrganizationId()) {
                $query          = $event->getQuery();
                $organizationId = $this->securityFacade->getOrganizationId();
                if ($organizationId) {
                    $query->andWhere(
                        'organization',
                        'in',
                        [$this->organizationProvider->getOrganizationId(), self::EMPTY_ORGANIZATION_ID],
                        'integer'
                    );
                }
                $event->setQuery($query);
            }
        } else {
            parent::beforeSearchEvent($event);
        }
    }
}
