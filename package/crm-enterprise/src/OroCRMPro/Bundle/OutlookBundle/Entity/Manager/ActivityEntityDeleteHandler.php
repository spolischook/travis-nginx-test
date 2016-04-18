<?php

namespace OroCRMPro\Bundle\OutlookBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityDeleteHandler as BaseHandler;

class ActivityEntityDeleteHandler extends BaseHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        if (!$this->securityFacade->isGranted('orocrmpro_outlook_integration')) {
            parent::checkPermissions($entity, $em);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissionsForTargetEntity($entity, ObjectManager $em)
    {
        if (!$this->securityFacade->isGranted('orocrmpro_outlook_integration')) {
            parent::checkPermissionsForTargetEntity($entity, $em);
        }
    }
}
