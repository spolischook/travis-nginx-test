<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;
use OroPro\Bundle\OrganizationBundle\EventListener\AuthenticationListener;

class AuthenticationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var AuthenticationListener */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->session  = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $this->listener = new AuthenticationListener($this->registry, $this->session);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->session, $this->registry);
    }

    public function testUnknownTokenShouldNotBeProcessed()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $event = new AuthenticationEvent($token);

        $this->assertFalse($this->listener->onAuthenticationSuccess($event));
    }

    public function testShouldSetPreferredOrganization()
    {
        $user                  = $this->getUser();
        $organization          = $this->getOrganization();
        $preferredOrganization = $this->getOrganization();
        $repo                  = $this->getRepository();
        $userPreference        = new UserPreferredOrganization($user, $preferredOrganization);

        $user->expects($this->any())->method('getOrganizations')->with($this->identicalTo(true))
            ->willReturn(new ArrayCollection([$organization, $preferredOrganization]));
        $repo->expects($this->once())->method('findOneBy')->with(['user' => $user])->willReturn($userPreference);
        $this->registry->expects($this->any())->method('getRepository')
            ->with('OroProOrganizationBundle:UserPreferredOrganization')->willReturn($repo);

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn($organization);
        $token->expects($this->any())->method('getUser')->willReturn($user);
        $token->expects($this->once())->method('setOrganizationContext')
            ->with($this->identicalTo($preferredOrganization));

        $event = new AuthenticationEvent($token);
        $this->assertNull($this->listener->onAuthenticationSuccess($event));
    }

    /**
     * @dataProvider activeOrganizationsProvider
     *
     * @param array $activeOrganizations
     * @param bool  $expectedSessionSet
     */
    public function testShouldSavePreferredOrganizationForFirstLogin($activeOrganizations, $expectedSessionSet)
    {
        $user         = $this->getUser();
        $organization = $this->getOrganization();
        $repo         = $this->getRepository();

        $user->expects($this->any())->method('getOrganizations')->with($this->identicalTo(true))
            ->willReturn(new ArrayCollection($activeOrganizations));
        $repo->expects($this->once())->method('findOneBy')->with(['user' => $user])->willReturn(false);
        $repo->expects($this->once())->method('savePreferredOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization));
        $this->registry->expects($this->any())->method('getRepository')
            ->with('OroProOrganizationBundle:UserPreferredOrganization')->willReturn($repo);

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn($organization);
        $token->expects($this->any())->method('getUser')->willReturn($user);
        $token->expects($this->never())->method('setOrganizationContext');

        if ($expectedSessionSet) {
            $user->expects($this->once())->method('getLoginCount')->willReturn(0);
            $this->session->expects($this->once())->method('set')
                ->with(AuthenticationListener::MULTIORG_LOGIN_FIRST, true);
        } else {
            $this->session->expects($this->never())->method('set');
        }

        $event = new AuthenticationEvent($token);
        $this->assertNull($this->listener->onAuthenticationSuccess($event));
    }

    /**
     * @return array
     */
    public function activeOrganizationsProvider()
    {
        return [
            'single active organization, do not notify user' => [
                '$activeOrganizations' => [$this->getOrganization()],
                '$expectedSessionSet'  => false
            ],
            'multiple active organizations, notify user'     => [
                '$activeOrganizations' => [$this->getOrganization(), $this->getOrganization()],
                '$expectedSessionSet'  => true
            ],
        ];
    }

    public function testShouldUpdatePreferredOrganizationIfGuessedAnotherOne()
    {
        $preferredOrganizationName = uniqid('OrganizationName');

        $user                  = $this->getUser();
        $organization          = $this->getOrganization(1);
        $preferredOrganization = $this->getOrganization(2);
        $repo                  = $this->getRepository();
        $userPreference        = new UserPreferredOrganization($user, $preferredOrganization);

        $preferredOrganization->expects($this->any())->method('getName')->willReturn($preferredOrganizationName);
        $user->expects($this->any())->method('getOrganizations')->with($this->identicalTo(true))
            ->willReturn(new ArrayCollection([$organization]));
        $repo->expects($this->once())->method('findOneBy')->with(['user' => $user])->willReturn($userPreference);
        $repo->expects($this->once())->method('updatePreferredOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization));
        $this->registry->expects($this->any())->method('getRepository')
            ->with('OroProOrganizationBundle:UserPreferredOrganization')->willReturn($repo);

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn($organization);
        $token->expects($this->any())->method('getUser')->willReturn($user);
        $token->expects($this->never())->method('setOrganizationContext');

        $this->session->expects($this->at(0))->method('set')
            ->with(AuthenticationListener::MULTIORG_LOGIN_UNPREFERRED, true);

        $event = new AuthenticationEvent($token);
        $this->assertNull($this->listener->onAuthenticationSuccess($event));
    }

    public function testOrganizationSwitchShouldRunSaveMechanism()
    {
        $user         = $this->getUser();
        $organization = $this->getOrganization();
        $repo         = $this->getRepository();

        $repo->expects($this->once())->method('updatePreferredOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization));
        $this->registry->expects($this->once())->method('getRepository')
            ->with('OroProOrganizationBundle:UserPreferredOrganization')->willReturn($repo);

        $event = new OrganizationSwitchAfter($user, $organization);
        $this->listener->onOrganizationSwitchAfter($event);
    }

    /**
     * @dataProvider interactiveLoginDataProvider
     *
     * @param TokenInterface $token
     * @param string         $actualProviders
     * @param string         $expectedProviders
     */
    public function testOnInteractiveLogin($token, $actualProviders, $expectedProviders)
    {
        $request = Request::create('');
        $request->query->set('_enableContentProviders', $actualProviders);

        $event = new InteractiveLoginEvent($request, $token);
        $this->listener->onInteractiveLogin($event);

        $this->assertEquals($expectedProviders, $request->get('_enableContentProviders'));
    }

    /**
     * @return array
     */
    public function interactiveLoginDataProvider()
    {
        $token   = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $RMToken = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\RememberMeToken')
            ->disableOriginalConstructor()->getMock();

        return [
            'regular token should be ignored'                           => [
                '$token'             => $token,
                '$actualProviders'   => '',
                '$expectedProviders' => '',
            ],
            'remember me token, should force enable content provider'   => [
                '$token'             => $RMToken,
                '$actualProviders'   => '',
                '$expectedProviders' => 'organization_switch',
            ],
            'remember me token, should not override existing providers' => [
                '$token'             => $RMToken,
                '$actualProviders'   => 'menu',
                '$expectedProviders' => 'menu,organization_switch',
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRepository()
    {
        return $this
            ->getMockBuilder('OroPro\Bundle\OrganizationBundle\Entity\Repository\UserPreferredOrganizationRepository')
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @param null $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUser($id = null)
    {
        $mock = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();

        if (null !== $id) {
            $mock->expects($this->any())->method('getId')->willReturn($id);
        }

        return $mock;
    }

    /**
     * @param null $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrganization($id = null)
    {
        $mock = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();

        if (null !== $id) {
            $mock->expects($this->any())->method('getId')->willReturn($id);
        }

        return $mock;
    }
}
