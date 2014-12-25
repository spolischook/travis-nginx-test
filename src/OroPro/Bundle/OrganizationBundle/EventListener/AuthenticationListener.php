<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;
use OroPro\Bundle\OrganizationBundle\Entity\Repository\UserPreferredOrganizationRepository;

class AuthenticationListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var SessionInterface */
    protected $session;

    /**
     * @param ManagerRegistry  $registry
     * @param SessionInterface $session
     */
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        $this->registry = $registry;
        $this->session  = $session;
    }

    /**
     * Listen security.authentication.success event
     *
     * @param AuthenticationEvent $event
     */
    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
        $token = $event->getAuthenticationToken();
        if (!$token instanceof OrganizationContextTokenInterface) {
            return;
        }

        /** @var User $user */
        $user = $token->getUser();
        /** @var Organization $organization */
        $organization = $token->getOrganizationContext();
        /** @var UserPreferredOrganization $preferredEntry */
        $preferredEntry = $this->getPreferredOrganizationRepository()->findOneBy(['user' => $user]);
        $preferredOrg   = $preferredEntry ? $preferredEntry->getOrganization() : false;

        if ($preferredOrg && $user->getOrganizations(true)->contains($preferredOrg)) {
            // we have preferred organization, it's active and user still assigned to it then use it
            $token->setOrganizationContext($preferredOrg);
        } elseif ($organization && $preferredOrg && $preferredOrg->getId() !== $organization->getId()) {
            // has preferred, but it's not available for particular user(disabled or unassigned)
            $this->getPreferredOrganizationRepository()->updatePreferredOrganization($user, $organization);

            // notify user that organization context currently activated is not expected preferred one
        } elseif ($organization) {
            // case if it's first login, just save preferred
            $this->getPreferredOrganizationRepository()->savePreferredOrganization($user, $organization);

            if ($user->getOrganizations(true)->count() > 1) {
                //notify that user is able to switch organization context
            }
        }
    }

    /**
     * Save last organization that user was switched to
     *
     * @param OrganizationSwitchAfter $event
     */
    public function onOrganizationSwitchAfter(OrganizationSwitchAfter $event)
    {
        $repo = $this->getPreferredOrganizationRepository();

        $repo->updatePreferredOrganization($event->getUser(), $event->getOrganization());
    }

    /**
     * Retrieve doctrine entity repository
     *
     * @return UserPreferredOrganizationRepository
     */
    protected function getPreferredOrganizationRepository()
    {
        return $this->registry->getRepository('OroProOrganizationBundle:UserPreferredOrganization');
    }
}
