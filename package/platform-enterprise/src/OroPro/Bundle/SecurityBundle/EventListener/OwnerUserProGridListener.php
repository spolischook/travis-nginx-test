<?php

namespace OroPro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\OwnerUserGridListener;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class OwnerUserProGridListener extends OwnerUserGridListener
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyACL(DatagridConfiguration $config, $accessLevel, User $user, Organization $organization)
    {
        // in System mode if additional organization was set - we should limit data by this organization
        if ($organization->getIsGlobal() && $this->organizationProvider->getOrganizationId()) {
            $organization = $this->organizationProvider->getOrganization();
            $accessLevel = AccessLevel::GLOBAL_LEVEL;
        }

        parent::applyAcl($config, $accessLevel, $user, $organization);
    }
}
