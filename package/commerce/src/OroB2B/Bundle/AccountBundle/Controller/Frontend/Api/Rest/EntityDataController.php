<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\EntityBundle\Controller\Api\Rest\EntityDataController as BaseController;

/**
 * @RouteResource("entity_data")
 * @NamePrefix("orob2b_api_frontend_")
 */
class EntityDataController extends BaseController
{
}
