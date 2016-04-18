<?php

namespace OroCRMPro\Bundle\OutlookBundle\Form\Handler;

use Oro\Bundle\ActivityBundle\Form\Handler\ActivityEntityApiHandler as BaseHandler;

class ActivityEntityApiHandler extends BaseHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity)
    {
        if (!$this->securityFacade->isGranted('orocrmpro_outlook_integration')) {
            parent::checkPermissions($entity);
        }
    }
}
