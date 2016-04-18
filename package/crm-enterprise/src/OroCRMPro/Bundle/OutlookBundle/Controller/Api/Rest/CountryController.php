<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * TODO: This controller should be removed with new API implementation.
 *
 * @RouteResource("outlook_country")
 * @NamePrefix("oro_api_")
 */
class CountryController extends FOSRestController
{
    /**
     * Get countries
     *
     * @Get(
     *      "/countries",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(
     *      description="Get countries",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        $this->checkCountryViewAccess();

        $items = $this->getDoctrine()->getRepository('OroAddressBundle:Country')->findAll();

        return $this->handleView(
            $this->view($items, is_array($items) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    protected function checkCountryViewAccess()
    {
        if ($this->getSecurityFacade()->isGranted('orocrmpro_outlook_integration')
            || $this->getSecurityFacade()->isGranted('oro_address_dictionaries_read')
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
