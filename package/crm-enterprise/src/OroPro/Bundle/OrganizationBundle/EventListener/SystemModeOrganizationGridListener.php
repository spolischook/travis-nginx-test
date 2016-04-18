<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class SystemModeOrganizationGridListener
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     * @param ManagerRegistry                      $doctrine
     * @param SecurityFacade                       $securityFacade
     */
    public function __construct(
        SystemAccessModeOrganizationProvider $organizationProvider,
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade
    ) {
        $this->organizationProvider = $organizationProvider;
        $this->doctrine             = $doctrine;
        $this->securityFacade       = $securityFacade;
    }

    /**
     * For grids we should check _sa_org_id parameter and if it was set
     * - set this organization to organization provider
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $currentOrganization = $this->securityFacade->getOrganization();
        if ($currentOrganization && $currentOrganization->getIsGlobal()) {
            $organizationId = $event->getDatagrid()->getParameters()->get('_sa_org_id');
            if ($organizationId) {
                $organization = $this->doctrine
                    ->getRepository('OroOrganizationBundle:Organization')
                    ->find((int)$organizationId);
                if ($organization) {
                    $this->organizationProvider->setOrganization($organization);
                }
            }
        }
    }
}
