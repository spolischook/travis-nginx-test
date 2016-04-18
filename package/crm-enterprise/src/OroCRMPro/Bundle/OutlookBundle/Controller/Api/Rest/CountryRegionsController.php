<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * TODO: This controller should be removed with new API implementation.
 *
 * @RouteResource("outlook_country_regions")
 * @NamePrefix("oro_api_")
 */
class CountryRegionsController extends FOSRestController
{
    /**
     * REST GET regions by country
     *
     * @param Country $country
     *
     * @Get(
     *      "/country/regions/{country}",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(
     *      description="Get regions by country id",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction(Country $country = null)
    {
        $this->checkRegionViewAccess();

        if (!$country) {
            return $this->handleView(
                $this->view(null, Codes::HTTP_NOT_FOUND)
            );
        }

        /** @var $regionRepository RegionRepository */
        $regionRepository = $this->getDoctrine()->getRepository('OroAddressBundle:Region');
        $regions = $regionRepository->getCountryRegions($country);

        return $this->handleView(
            $this->view($regions, Codes::HTTP_OK)
        );
    }

    protected function checkRegionViewAccess()
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
