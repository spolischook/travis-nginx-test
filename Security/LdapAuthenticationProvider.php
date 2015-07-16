<?php

namespace OroCRMPro\Bundle\LDAPBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider;

class LdapAuthenticationProvider extends UsernamePasswordOrganizationAuthenticationProvider
{
    /** @var LdapAuthenticator */
    private $ldapAuthenticator;

    /**
     * @param UserProviderInterface   $userProvider
     * @param UserCheckerInterface    $userChecker
     * @param string                  $providerKey
     * @param EncoderFactoryInterface $encoderFactory
     * @param bool                    $hideUserNotFoundExceptions
     * @param LdapAuthenticator       $ldapAuthenticator
     */
    public function __construct(
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        EncoderFactoryInterface $encoderFactory,
        $hideUserNotFoundExceptions,
        LdapAuthenticator $ldapAuthenticator
    ) {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions);

        $this->ldapAuthenticator = $ldapAuthenticator;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $currentUser = $token->getUser();
        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getLdapDistinguishedNames() !== $user->getLdapDistinguishedNames()) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if ('' === ($presentedPassword = $token->getCredentials())) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            if (!$this->ldapAuthenticator->check($user, $presentedPassword)) {
                throw new BadCredentialsException('The presented password is invalid.');
            }
        }
    }
}
