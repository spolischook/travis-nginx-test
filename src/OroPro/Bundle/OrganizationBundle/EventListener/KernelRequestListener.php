<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Router;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

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

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     * @param ManagerRegistry                      $doctrine
     * @param SecurityFacade                       $securityFacade
     * @param Router                               $router
     * @param WorkflowManager                      $workflowManager
     * @param OwnershipMetadataProvider            $metadataProvider
     */
    public function __construct(
        SystemAccessModeOrganizationProvider $organizationProvider,
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        Router $router,
        WorkflowManager $workflowManager,
        OwnershipMetadataProvider $metadataProvider
    ) {
        $this->organizationProvider = $organizationProvider;
        $this->doctrine             = $doctrine;
        $this->securityFacade       = $securityFacade;
        $this->router               = $router;
        $this->workflowManager      = $workflowManager;
        $this->metadataProvider     = $metadataProvider;
    }

    /**
     * Set organization to organization provider if in request where is additional parameter _sa_org_id and user
     * work in the system access organization or redirect to the select organization form in case of workflow
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $currentOrganization = $this->securityFacade->getOrganization();
        if ($currentOrganization && $currentOrganization->getIsGlobal()) {
            $request  = $event->getRequest();

            //try to find selected organization from request and set it into organization provider
            $selectedOrganizationId = $this->getOrganizationIdFromRequest($request);

            if ($selectedOrganizationId) {
                $organization = $this
                    ->doctrine
                    ->getRepository('OroOrganizationBundle:Organization')
                    ->find((int)$selectedOrganizationId);
                if ($organization) {
                    $this->organizationProvider->setOrganization($organization);
                }
            } else {
                //in case of workflow we should check related workflow entity
                //and if related entity has organization field - redirect to select organization form
                if ('oro_workflow_start_transition_form' == $request->attributes->get('_route')) {
                    $relatedEntity = $this->workflowManager
                        ->getWorkflow($request->attributes->get('workflowName'))
                        ->getDefinition()
                        ->getRelatedEntity();
                    $metadata = $this->metadataProvider->getMetadata($relatedEntity);
                    if ($metadata
                        && $metadata->getOrganizationFieldName()
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

        return $selectedOrganizationId;
    }
}
