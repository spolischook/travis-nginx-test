<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;

/**
 * TODO: This controller should be removed with new API implementation.
 *
 * @RouteResource("configuration_outlook")
 * @NamePrefix("oro_api_")
 */
class ConfigurationController extends FOSRestController
{
    /**
     * Get all configuration data of the specified Outlook section.
     *
     * @param string $path The configuration section path. For example: outlook or outlook/layout
     *
     * @Get("/configuration/{path}",
     *      requirements={"path"="localization|outlook(\/[\w-]+)*"}
     * )
     * @return Response
     */
    public function getAction($path)
    {
        $this->checkConfigurationAccess();

        $manager = $this->get('orocrmpro_outlook.config_manager.api');

        try {
            $data = $manager->getData($path, $this->getRequest()->get('scope', 'user'));
        } catch (ItemNotFoundException $e) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }

    protected function checkConfigurationAccess()
    {
        if ($this->getSecurityFacade()->isGranted('orocrmpro_outlook_integration')
            || $this->getSecurityFacade()->isGranted('oro_config_system')
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
