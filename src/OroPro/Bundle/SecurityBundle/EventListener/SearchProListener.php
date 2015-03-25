<?php

namespace OroPro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
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
    public function prepareEntityMapEvent(PrepareEntityMapEvent $event)
    {
        $className = $event->getClassName();
        $metadata  = $this->metadataProvider->getMetadata($className);

        if ($metadata && $metadata->isGlobalView()) {
            $data                            = $event->getData();
            $data['integer']['organization'] = self::EMPTY_ORGANIZATION_ID;
            $event->setData($data);
            return null;
        }

        parent::prepareEntityMapEvent($event);
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
            $organizationId = $this->organizationProvider->getOrganizationId();
            if ($organizationId) {
                $query = $event->getQuery();
                $query->andWhere('organization', 'in', [$organizationId, self::EMPTY_ORGANIZATION_ID], 'integer');
                $event->setQuery($query);
            }
        } else {
            parent::beforeSearchEvent($event);
        }
    }
}
