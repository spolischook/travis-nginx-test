<?php

namespace OroPro\Bundle\SecurityBundle\Search;

use Oro\Bundle\SecurityBundle\Search\AclHelper as BaseAclHelper;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class AclHelper extends BaseAclHelper
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
    public function getClassNamesBySharingScopes(array $shareScopes)
    {
        $result = parent::getClassNamesBySharingScopes($shareScopes);

        foreach ($shareScopes as $shareScope) {
            if ($shareScope === Share::SHARE_SCOPE_ORGANIZATION) {
                array_unshift($result, 'Oro\Bundle\OrganizationBundle\Entity\Organization');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOrganizationId()
    {
        $organization = $this->securityFacade->getOrganization();
        if ($organization && $organization->getIsGlobal()) {
            // in System access mode we must check organization id in the organization Provider and if
            // it is not null - use it to limit search data
            return $this->organizationProvider->getOrganizationId();
        }

        return parent::getOrganizationId();
    }
}
