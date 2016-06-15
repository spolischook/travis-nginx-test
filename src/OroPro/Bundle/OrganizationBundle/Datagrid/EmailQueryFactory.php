<?php

namespace OroPro\Bundle\OrganizationBundle\Datagrid;

use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory as BaseEmailQueryFactory;

class EmailQueryFactory extends BaseEmailQueryFactory
{
    /**
     * {@inheritdoc}
     */
    protected function getOrganization()
    {
        $organization = parent::getOrganization();

        return $organization->getIsGlobal() ? null : $organization;
    }
}
