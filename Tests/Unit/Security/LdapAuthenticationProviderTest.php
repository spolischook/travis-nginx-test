<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRMPro\Bundle\LDAPBundle\Security\LdapAuthenticationProvider;
use OroCRMPro\Bundle\LDAPBundle\Security\LdapAuthenticator;
use OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class LdapAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    private $providerKey = 'orocrmpro_ldap';
    private $userProvider;
    /** @var LdapAuthenticator */
    private $ldapAuthenticator;
    /** @var LdapAuthenticationProvider */
    private $ldapProvider;

    public function setUp()
    {
        $userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');

        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');

        $encoderFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');

        $this->ldapAuthenticator= $this->getMockBuilder('OroCRMPro\Bundle\LDAPBundle\Security\LdapAuthenticator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ldapProvider = new LdapAuthenticationProvider(
            $this->userProvider,
            $userChecker,
            $this->providerKey,
            $encoderFactory,
            true,
            $this->ldapAuthenticator
        );
    }
    
    public function testTokenShouldBeAuthenticated()
    {
        $token = new UsernamePasswordToken('user', 'credentials', $this->providerKey);

        $organization = new Organization();
        $organization->setEnabled(true);

        $user = new TestingUser();
        $user->addOrganization($organization);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('user')
            ->will($this->returnValue($user));

        $this->ldapAuthenticator->expects($this->once())
            ->method('check')
            ->will($this->returnValue(true));

        $resultToken = $this->ldapProvider->authenticate($token);

        $this->assertInstanceOf(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken',
            $resultToken
        );
        $this->assertSame($user, $resultToken->getUser());
        $this->assertEquals('credentials', $resultToken->getCredentials());
        $this->assertEquals($this->providerKey, $resultToken->getProviderKey());
        $this->assertEquals($organization, $resultToken->getOrganizationContext());
    }
}
