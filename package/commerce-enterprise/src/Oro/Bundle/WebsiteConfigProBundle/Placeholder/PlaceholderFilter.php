<?php

namespace Oro\Bundle\WebsiteConfigProBundle\Placeholder;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PlaceholderFilter
{
    /**
     * Check if we on view website page
     *
     * @param object $entity
     * @return bool
     */
    public function isWebsitePage($entity)
    {
        return $entity instanceof Website;
    }
}
