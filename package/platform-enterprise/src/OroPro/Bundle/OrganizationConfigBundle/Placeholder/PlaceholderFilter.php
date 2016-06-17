<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Placeholder;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class PlaceholderFilter
{
    /**
     * Check if we on view organization page
     *
     * @param object $entity
     * @return bool
     */
    public function isOrganizationPage($entity)
    {
        return $entity instanceof Organization;
    }
}
