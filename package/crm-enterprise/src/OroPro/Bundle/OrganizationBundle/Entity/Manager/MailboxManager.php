<?php

namespace OroPro\Bundle\OrganizationBundle\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager as BaseMailboxManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class MailboxManager extends BaseMailboxManager
{
    /**
     * {@inheritdoc}
     */
    public function findAvailableOrigins(User $user, Organization $organization)
    {
        if (!$organization->getIsGlobal()) {
            return parent::findAvailableOrigins($user, $organization);
        }

        return $this->registry->getRepository('OroEmailBundle:EmailOrigin')->findBy([
            'owner' => $user,
            'isActive' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function createAvailableMailboxesQuery($user, Organization $organization = null)
    {
        /*
         * If global organization is used, don't filter by organization.
         */
        if (!$organization || $organization->getIsGlobal()) {
            $organization = null;
        }

        return parent::createAvailableMailboxesQuery($user, $organization);
    }
}
