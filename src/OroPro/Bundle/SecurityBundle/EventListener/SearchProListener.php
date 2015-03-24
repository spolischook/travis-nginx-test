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
        $data           = $event->getData();
        $className      = $event->getClassName();
        $entity         = $event->getEntity();
        $organizationId = self::EMPTY_ORGANIZATION_ID;
        $metadata       = $this->metadataProvider->getMetadata($className);

        if ($metadata) {
            $additionalData = $metadata->getAdditionalParameters();

            if (!empty($additionalData['global_view']) && 'true' === $additionalData['global_view']) {
                $data['integer']['organization'] = $organizationId;
                $event->setData($data);
                return null;
            }

            $organizationField = null;
            if ($metadata->getOrganizationFieldName()) {
                $organizationField = $metadata->getOrganizationFieldName();
            }

            if ($metadata->isOrganizationOwned()) {
                $organizationField = $metadata->getOwnerFieldName();
            }

            if ($organizationField) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                /** @var Organization $organization */
                $organization = $propertyAccessor->getValue($entity, $organizationField);
                if ($organization && null !== $organization->getId()) {
                    $organizationId = $organization->getId();
                }
            }
        }

        $data['integer']['organization'] = $organizationId;

        $event->setData($data);
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
