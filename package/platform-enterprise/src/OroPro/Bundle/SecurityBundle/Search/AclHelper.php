<?php

namespace OroPro\Bundle\SecurityBundle\Search;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Search\AclHelper as BaseAclHelper;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;

class AclHelper extends BaseAclHelper
{
    /** @var  RequestStack */
    protected $request;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var OrganizationProHelper */
    protected $organizationHelper;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @param OrganizationProHelper $organizationHelper
     */
    public function setOrganizationProHelper(OrganizationProHelper $organizationHelper)
    {
        $this->organizationHelper = $organizationHelper;
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
        $globalOrgId = $this->organizationHelper->getGlobalOrganizationId();
        $currentRequest = $this->request->getCurrentRequest();
        if ($this->isApplicableOrganizationFilter($organization, $globalOrgId, $currentRequest)) {
            $organizations = $currentRequest->get('organizations');
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

        parent::addOrganizationLimits($query, $expr);
    }

    /**
     * Check if organization is system access or we do not have global organization
     * to add logic filter by requested organization
     *
     * @param $organization
     * @param $globalOrgId
     * @param $currentRequest
     *
     * @return bool
     */
    protected function isApplicableOrganizationFilter($organization, $globalOrgId, $currentRequest)
    {
        return (($organization && $organization->getIsGlobal()) || !$globalOrgId)
        && ($currentRequest && $currentRequest->query->has('organizations'));
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
