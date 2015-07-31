<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;

/**
 * @RouteResource("configuration_outlook")
 * @NamePrefix("oro_api_")
 */
class ConfigurationController extends FOSRestController
{
    /**
     * Get all configuration data of the specified outlook section.
     *
     * @param string $path The configuration section path. For example: outlook/layout
     *
     * @Get("/configuration/{path}",
     *      requirements={"path"="outlook(\/[\w-]*)*"}
     * )
     * @ApiDoc(
     *      description="Get all configuration data of the specified outlook section",
     *      resource=true,
     *      filters={
     *          {"name"="scope", "dataType"="string", "description"="Scope name. By default - user"},
     *          {"name"="locale", "dataType"="string", "description"="The preferred locale for configuration values"}
     *      }
     * )
     * @Acl(
     *      id="orocrmpro_outlook_integration",
     *      label="orocrmpro_outlook.integration.label",
     *      type="action",
     *      group_name=""
     * )
     *
     * @return Response
     */
    public function getAction($path)
    {
        $manager = $this->get('oro_config.manager.api');

        try {
            $data = $manager->getData($path, $this->getRequest()->get('scope', 'user'));
        } catch (ItemNotFoundException $e) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }
}
