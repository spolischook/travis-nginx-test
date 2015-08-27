<?php

namespace OroPro\Bundle\OrganizationBundle\Grid;

use Oro\Bundle\EmailBundle\Datagrid\MailboxChoiceList as BaseMailboxChoiceList;

class MailboxChoiceList extends BaseMailboxChoiceList
{
    /**
     * {@inheritdoc}
     */
    protected function getOrganization()
    {
        $organization = parent::getOrganization();
        if ($organization && $organization->getIsGlobal()) {
            return null;
        }

        return $organization;
    }
}
