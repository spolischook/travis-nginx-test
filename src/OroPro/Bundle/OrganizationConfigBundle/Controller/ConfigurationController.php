<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ConfigurationController extends Controller
{
    /**
     * @Route(
     *      "/organization/{id}/{activeGroup}/{activeSubGroup}",
     *      name="oropro_config_configuration_organization",
     *      requirements={"id"="\d+"},
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     * @AclAncestor("oro_organization_update")
     */
    public function organizationConfigAction(Organization $entity, $activeGroup = null, $activeSubGroup = null)
    {
        return [
            'entity' => $entity
        ];
    }
}
