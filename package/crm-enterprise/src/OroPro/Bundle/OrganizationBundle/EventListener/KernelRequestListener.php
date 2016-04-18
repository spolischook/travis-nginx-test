<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Router;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class KernelRequestListener
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var Router */
    protected $router;

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var OwnershipMetadataProvider */
    protected $metadataProvider;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     * @param ManagerRegistry                      $doctrine
     * @param SecurityFacade                       $securityFacade
     * @param Router                               $router
     * @param WorkflowManager                      $workflowManager
     * @param OwnershipMetadataProvider            $metadataProvider
     * @param EntityRoutingHelper                  $routingHelper
     */
    public function __construct(
        SystemAccessModeOrganizationProvider $organizationProvider,
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        Router $router,
        WorkflowManager $workflowManager,
        OwnershipMetadataProvider $metadataProvider,
        EntityRoutingHelper $routingHelper
    ) {
        $this->organizationProvider = $organizationProvider;
        $this->doctrine             = $doctrine;
        $this->securityFacade       = $securityFacade;
        $this->router               = $router;
        $this->workflowManager      = $workflowManager;
        $this->metadataProvider     = $metadataProvider;
        $this->routingHelper        = $routingHelper;
    }

    /**
     * Set organization into organization provider if request has additional parameter "_sa_org_id" and user
     * works in the system access organization.
     * In case of workflow - redirect to the select organization form.
     * In case of edit custom entity record - set organization into organization provider
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $currentOrganization = $this->securityFacade->getOrganization();
        if ($currentOrganization && $currentOrganization->getIsGlobal()) {
            $request = $event->getRequest();

            /**
             * Try to find selected organization from request and set it into organization provider
             */
            $selectedOrganizationId = $this->getOrganizationIdFromRequest($request);
            if ($selectedOrganizationId) {
                $organization = $this->doctrine
                    ->getRepository('OroOrganizationBundle:Organization')
                    ->find((int)$selectedOrganizationId);
                if ($organization) {
                    $this->organizationProvider->setOrganization($organization);
                }
            } elseif ('oro_entity_update' == $request->attributes->get('_route')) {
                /**
                 * In case of edit custom entity record we should take organization from this record
                 * and set it into organization provider
                 */
                $entityClass = $this->routingHelper->resolveEntityClass($request->attributes->get('entityName'));
                if ($entityClass && $request->attributes->get('id')) {
                    $entity  = $this->doctrine->getRepository($entityClass)->find($request->attributes->get('id'));
                    $organizationField = $this->metadataProvider->getMetadata($entityClass)->getGlobalOwnerFieldName();
                    if ($organizationField) {
                        $accessor = PropertyAccess::createPropertyAccessor();
                        $this->organizationProvider->setOrganization($accessor->getValue($entity, $organizationField));
                    }
                }
            } else {
                /**
                 * In case of workflow we should check related workflow entity
                 * and if related entity has organization field - redirect to select organization form
                 */
                if ('oro_workflow_start_transition_form' == $request->attributes->get('_route')) {
                    $relatedEntity = $this->workflowManager
                        ->getWorkflow($request->attributes->get('workflowName'))
                        ->getDefinition()
                        ->getRelatedEntity();
                    if ($this->metadataProvider->getMetadata($relatedEntity)->getGlobalOwnerFieldName()
                        && $request->query->get('entityId') == '0'
                    ) {
                        $event->setResponse(
                            new RedirectResponse(
                                $this->router->generate(
                                    'oropro_organization_selector_form',
                                    ['form_url' => $request->getUri()]
                                )
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Get organization id from the request
     *
     * @param Request $request
     *
     * @return string|null
     */
    protected function getOrganizationIdFromRequest(Request $request)
    {
        $selectedOrganizationId = null;

        $formRequest = $request->get('form');
        if (!$formRequest) {
            $formRequest = $request->get('oro_workflow_transition');
        }
        if ($formRequest && isset($formRequest['_sa_org_id'])) {
            $selectedOrganizationId = $formRequest['_sa_org_id'];
        } else {
            $selectedOrganizationId = $request->get('_sa_org_id');
        }
        if (!$selectedOrganizationId) {
            $selectedOrganizationId = $this->getContextOrganization($request);
        }

        return $selectedOrganizationId;
    }

    /**
     * @param Request $request
     * @return string|null
     */
    protected function getContextOrganization(Request $request)
    {
        $selectedOrganizationId = null;
        $entityId = $request->get('entityId');
        $entityClass = $request->get('entityClass');
        if ($entityId && $entityClass) {
            $targetEntity = $this->routingHelper->getEntity($entityClass, $entityId);
            if ($targetEntity) {
                $organizationField = $this->metadataProvider->getMetadata($entityClass)->getGlobalOwnerFieldName();
                if ($organizationField) {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    /** @var Organization $selectedOrganization */
                    $selectedOrganization = $accessor->getValue($targetEntity, $organizationField);
                    return $selectedOrganization->getId();
                }
            }
        }

        return $selectedOrganizationId;
    }
}
