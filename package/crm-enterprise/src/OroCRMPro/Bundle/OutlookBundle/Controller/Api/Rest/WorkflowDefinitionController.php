<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\FOSRestController;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * TODO: This controller should be removed with new API implementation.
 *
 * @NamePrefix("oro_api_workflow_definition_")
 */
class WorkflowDefinitionController extends FOSRestController
{
    /**
     * REST GET item
     *
     * @param WorkflowDefinition $workflowDefinition
     *
     * @Get(
     *      "/workflowdefinition/{workflowDefinition}",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(
     *      description="Get workflow definition",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction(WorkflowDefinition $workflowDefinition)
    {
        $this->checkWorkflowDefinitionViewAccess($workflowDefinition);

        return $this->handleView($this->view($workflowDefinition, Codes::HTTP_OK));
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    protected function checkWorkflowDefinitionViewAccess(WorkflowDefinition $workflowDefinition)
    {
        if ($this->getSecurityFacade()->isGranted('orocrmpro_outlook_integration')
            || $this->getSecurityFacade()->isGranted('oro_workflow_definition_view', $workflowDefinition)
        ) {
            return;
        }
        throw new AccessDeniedException();
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }
}
