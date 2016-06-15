<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity;
use Oro\Component\Action\Action\CreateEntity;
use Oro\Component\Action\Event\ExecuteActionEvent;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class WorkflowListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProvider */
    protected $metadataProvider;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     * @param SecurityFacade                       $securityFacade
     * @param DoctrineHelper                       $doctrineHelper
     * @param OwnershipMetadataProvider            $metadataProvider
     */
    public function __construct(
        SystemAccessModeOrganizationProvider $organizationProvider,
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProvider $metadataProvider
    ) {
        $this->securityFacade       = $securityFacade;
        $this->organizationProvider = $organizationProvider;
        $this->doctrineHelper       = $doctrineHelper;
        $this->metadataProvider     = $metadataProvider;
    }

    /**
     * If user works in system access organization and additional organization was selected,
     * on CreateRelatedEntity - set organization to the entity
     *
     * @param ExecuteActionEvent $event
     */
    public function onBeforeAction(ExecuteActionEvent $event)
    {
        $currentOrg = $this->securityFacade->getOrganization();
        if ($currentOrg && $currentOrg->getIsGlobal() && $this->organizationProvider->getOrganization()) {
            $action  = $event->getAction();
            $context = $event->getContext();
            if (($action instanceof CreateRelatedEntity || $action instanceof CreateEntity)
                && $context instanceof WorkflowItem
            ) {
                $entity            = $context->getEntity();
                $entityClass       = $this->doctrineHelper->getEntityClass($entity);
                $organizationField = $this->metadataProvider
                    ->getMetadata($entityClass)
                    ->getOrganizationFieldName();
                if ($organizationField) {
                    $propertyAccessor = PropertyAccess::createPropertyAccessor();
                    $propertyAccessor->setValue(
                        $entity,
                        $organizationField,
                        $this->organizationProvider->getOrganization()
                    );
                }
            }
        }
    }

    /**
     * Add additional url parameter with selected organization
     *
     * @param StartTransitionEvent $event
     */
    public function onStartTransition(StartTransitionEvent $event)
    {
        $currentOrg = $this->securityFacade->getOrganization();
        if ($currentOrg && $currentOrg->getIsGlobal() && $this->organizationProvider->getOrganizationId()) {
            $routeParameters               = $event->getRouteParameters();
            $routeParameters['_sa_org_id'] = $this->organizationProvider->getOrganizationId();
            $event->setRouteParameters($routeParameters);
        }
    }
}
