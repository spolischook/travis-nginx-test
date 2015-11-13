<?php

namespace OroPro\Bundle\SecurityBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Handler\UserDeleteHandler as OroUserDeleteHandler;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

class UserDeleteHandler extends OroUserDeleteHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        if ($this->securityFacade->hasUserSidSharedRecords($entity)) {
            throw new ForbiddenException('user has shared records');
        }

        parent::checkPermissions($entity, $em);
    }
}
