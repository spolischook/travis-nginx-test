<?php

namespace OroPro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;

class SearchProListener extends SearchListener
{
    /**
     * {@inheritdoc}
     */
    public function beforeSearchEvent(BeforeSearchEvent $event)
    {
        //In global mode we should not add organization limits
        $organization = $this->securityFacade->getOrganization();
        if ($organization && !$organization->getIsGlobal()) {
            parent::beforeSearchEvent($event);
        }
    }
}
