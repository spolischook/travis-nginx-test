<?php

namespace OroPro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class SearchProListener extends SearchListener
{
    /**
     * {@inheritdoc}
     */
    protected function getOrganizationId(OwnershipMetadata $metadata, $entity)
    {
        if ($metadata->isGlobalView()) {
            return self::EMPTY_ORGANIZATION_ID;
        }

        return parent::getOrganizationId($metadata, $entity);
    }
}
