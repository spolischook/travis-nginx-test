<?php

namespace Oro\Bundle\SSOBundle\Tests\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SSOBundle\Security\OAuthProvider;
use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\UserBundle\Entity\User;

class OAuthProviderTest extends \PHPUnit_Framework_TestCase
{
    private $oauthProvider;
    private $userProvider;
    private $resourceOwnerMap;
    private $userChecker;

    public function setUp()
    {
        $this->userProvider = $this
                ->getMock('HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface');
        $this->resourceOwnerMap = $this->getMockBuilder('HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap')
                ->disableOriginalConstructor()
                ->getMock();
        $this->userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        
        $this->oauthProvider = new OAuthProvider($this->userProvider, $this->resourceOwnerMap, $this->userChecker);
    }

    public function testSupportsShuldReturnTrueForOAuthToken()
    {
        $this->resourceOwnerMap->expects($this->once())
            ->method('hasResourceOwnerByName')
            ->with($this->equalTo('google'))
            ->will($this->returnValue(true));

        $token = new OAuthToken('token');
        $token->setResourceOwnerName('google');
        $this->assertTrue($this->oauthProvider->supports($token));
    }
    
    public function testTokenShouldBeAuthenticated()
    {
        $token = new OAuthToken('token');
        $token->setResourceOwnerName('google');
        $organization = new Organization();
        $organization->setEnabled(true);
        $token->setOrganizationContext($organization);

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        
        $resourceOwner = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface');
        $resourceOwner
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('google'));

        $resourceOwner
            ->expects($this->any())
            ->method('getUserInformation')
            ->will($this->returnValue($userResponse));

        $this->resourceOwnerMap
            ->expects($this->any())
            ->method('getResourceOwnerByName')
            ->will($this->returnValue($resourceOwner));

        $user = new User();
        $user->addOrganization($organization);

        $this->userProvider
            ->expects($this->any())
            ->method('loadUserByOAuthUserResponse')
            ->with($userResponse)
            ->will($this->returnValue($user));

        $resultToken = $this->oauthProvider->authenticate($token);
        $this->assertInstanceOf('Oro\Bundle\SSOBundle\Security\OAuthToken', $resultToken);
        $this->assertSame($user, $resultToken->getUser());
        $this->assertEquals('google', $resultToken->getResourceOwnerName());
        $this->assertTrue($resultToken->isAuthenticated());
    }
}
