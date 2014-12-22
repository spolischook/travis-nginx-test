<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class ZeroStepOrganizationListener
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     * @param ManagerRegistry                      $doctrine
     */
    public function __construct(SystemAccessModeOrganizationProvider $organizationProvider, ManagerRegistry $doctrine)
    {
        $this->organizationProvider = $organizationProvider;
        $this->doctrine             = $doctrine;
    }

    /**
     * Set organization to organization provider if in request where is additional parameter _sa_org_id
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $zeroStepOrganization = null;
        $request              = $event->getRequest();
        $formRequest          = $request->get('form');
        if ($formRequest && isset($formRequest['_sa_org_id'])) {
            $zeroStepOrganization = $formRequest['_sa_org_id'];
        } else {
            $zeroStepOrganization = $event->getRequest()->get('_sa_org_id');
        }

        if ($zeroStepOrganization) {
            $organization = $this
                ->doctrine
                ->getRepository('OroOrganizationBundle:Organization')
                ->find((int)$zeroStepOrganization);
            if ($organization) {
                $this->organizationProvider->setOrganization($organization);
            }
        }
    }
}
