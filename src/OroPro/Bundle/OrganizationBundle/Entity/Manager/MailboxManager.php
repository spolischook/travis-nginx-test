<?php

namespace OroPro\Bundle\OrganizationBundle\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager as BaseMailboxManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class MailboxManager extends BaseMailboxManager
{
    /**
     * {@inheritdoc}
     * @param Organization
     */
    public function findAvailableMailboxes($user, Organization $organization)
    {
        /*
         * If global organization is used, don't filter by organization.
         */
        if ($organization->getIsGlobal()) {
            $organization = null;
        }

        $qb = $this->registry->getRepository('OroEmailBundle:Mailbox')
            ->createAvailableMailboxesQuery($user, $organization);

        return $qb->getQuery()->getResult();
    }
}
