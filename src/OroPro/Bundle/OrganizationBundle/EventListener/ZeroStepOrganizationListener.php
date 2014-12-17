<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use OroPro\Bundle\OrganizationBundle\Provider\OrganizationIdProvider;

class ZeroStepOrganizationListener
{
    /** @var OrganizationIdProvider */
    protected $organizationIdProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param OrganizationIdProvider $organizationIdProvider
     */
    public function __construct(OrganizationIdProvider $organizationIdProvider, ManagerRegistry $doctrine)
    {
        $this->organizationIdProvider = $organizationIdProvider;
        $this->doctrine               = $doctrine;
    }

    /**
     * Set organization to organization provider if in request where is additional parameter _sa_org_id
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $zeroStepOrganization = null;
        $request = $event->getRequest();
        $formRequest = $request->get('form');
        if ($formRequest && isset($formRequest['_sa_org_id'])) {
            $zeroStepOrganization = $formRequest['_sa_org_id'];
        }
        if (!$zeroStepOrganization) {
            $zeroStepOrganization = $event->getRequest()->get('_sa_org_id');
        }

        if ($zeroStepOrganization) {
            $this->organizationIdProvider->setOrganization(
                $this->doctrine->getRepository('OroOrganizationBundle:Organization')->find((int)$zeroStepOrganization)
            );
        }
    }
}
