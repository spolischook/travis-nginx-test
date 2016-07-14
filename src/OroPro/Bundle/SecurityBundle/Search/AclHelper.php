<?php

namespace OroPro\Bundle\SecurityBundle\Search;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Search\AclHelper as BaseAclHelper;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class AclHelper extends BaseAclHelper
{
    /** @var  RequestStack */
    protected $request;

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
     * @param RequestStack $request
     */
    public function setRequest(RequestStack $request)
    {
        $this->request = $request;
    }

    /**
     * @param Query $query
     * @param $expr
     */
    protected function addOrganizationLimits(Query $query, $expr)
    {
        $organization = $this->securityFacade->getOrganization();
        if ($organization && $organization->getIsGlobal()) {
            if ($this->request->getCurrentRequest()->query->has('organizations')) {
                $organizations = $this->request->getCurrentRequest()->get('organizations');
                if (strlen($organizations) > 0) {
                    $organizations = explode(',', $organizations);

                    if (count($organizations) > 1
                        || (count($organizations) === 1 && $this->getOrganizationId() !== current($organizations))
                    ) {
                        $query->getCriteria()->andWhere($expr->in('integer.organization', $organizations));

                        return;
                    }
                } else {
                    $query->getCriteria()->andWhere($expr->in('integer.organization', [0]));

                    return;
                }
            }
        }

        parent::addOrganizationLimits($query, $expr);
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
