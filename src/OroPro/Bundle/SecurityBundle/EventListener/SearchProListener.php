<?php

namespace OroPro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;

use OroPro\Bundle\OrganizationBundle\Provider\OrganizationIdProvider;

class SearchProListener extends SearchListener
{
    /** @var OrganizationIdProvider */
    protected $organizationIdProvider;

    /**
     * @param OrganizationIdProvider $organizationIdProvider
     */
    public function setOrganizationIdProvider(OrganizationIdProvider $organizationIdProvider)
    {
        $this->organizationIdProvider = $organizationIdProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSearchEvent(BeforeSearchEvent $event)
    {
        $organization = $this->securityFacade->getOrganization();
        if ($organization && $organization->getIsGlobal()) {
            // in System access mode we must check organization id in the organizationIdProvider and if
            // it is not null - use it to limit search data
            if ($this->organizationIdProvider->getOrganizationId()) {
                $query          = $event->getQuery();
                $organizationId = $this->securityFacade->getOrganizationId();
                if ($organizationId) {
                    $query->andWhere(
                        'organization',
                        'in',
                        [$this->organizationIdProvider->getOrganizationId(), self::EMPTY_ORGANIZATION_ID],
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
