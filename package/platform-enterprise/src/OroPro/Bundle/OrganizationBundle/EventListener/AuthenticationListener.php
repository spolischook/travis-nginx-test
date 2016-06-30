<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\WsseToken;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;
use OroPro\Bundle\OrganizationBundle\Entity\Repository\UserPreferredOrganizationRepository;

class AuthenticationListener
{
    const MULTIORG_LOGIN_FIRST       = 'oropro_organization_mlf';
    const MULTIORG_LOGIN_UNPREFERRED = 'oropro_organization_mlu';

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
     * Checks whether user has preferable organization and it's available for him right now then replace
     * current organization on preferable. Also handles first login, or login to organization different
     * from preferable organization and notifies user about this.
     * Listen `security.authentication.success` event
     *
     * @param AuthenticationEvent $event
     *
     * @return bool|void
     */
    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
        $token = $event->getAuthenticationToken();
        if (!$token instanceof OrganizationContextTokenInterface) {
            return false;
        }

        /**
         * In case of API request organization should not be changed.
         */
        if ($token instanceof WsseToken) {
            return true;
        }

        if (!$token->getUser() instanceof User) {
            return false;
        }

        $this->managePreferredOrganization($token);
    }

    /**
     * Save last organization that user was switched to
     * Listen `oro_security.event.organization_switch.after` event
     *
     * @param OrganizationSwitchAfter $event
     */
    public function onOrganizationSwitchAfter(OrganizationSwitchAfter $event)
    {
        $repo = $this->getPreferredOrganizationRepository();

        $repo->updatePreferredOrganization($event->getUser(), $event->getOrganization());
    }

    /**
     * Listen `security.interactive_login` see if user logged in via remember me than force update part of the page
     *
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($event->getAuthenticationToken() instanceof RememberMeToken) {
            $providers = $event->getRequest()->get('_enableContentProviders', '');

            $providers   = array_filter(explode(',', $providers));
            $providers[] = 'organization_switch';

            $event->getRequest()->query->set('_enableContentProviders', implode(',', array_unique($providers)));
        }
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

    /**
     * @param TokenInterface $token
     */
    protected function managePreferredOrganization(TokenInterface $token)
    {
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
            $this->session->set(self::MULTIORG_LOGIN_UNPREFERRED, true);
        } elseif ($organization) {
            // case if it's first login, just save preferred
            $this->getPreferredOrganizationRepository()->savePreferredOrganization($user, $organization);
        
            if ($user->getOrganizations(true)->count() > 1 && $user->getLoginCount() === 0) {
                //notify that user is able to switch organization context
                $this->session->set(self::MULTIORG_LOGIN_FIRST, true);
            }
        }
    }
}
