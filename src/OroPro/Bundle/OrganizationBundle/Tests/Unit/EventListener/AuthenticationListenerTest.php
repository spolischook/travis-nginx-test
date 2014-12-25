<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

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
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $token->expects($this->once())->method('setOrganizationContext')
            ->with($this->identicalTo($preferredOrganization));

        $event = new AuthenticationEvent($token);
        $this->assertNull($this->listener->onAuthenticationSuccess($event));
    }

    public function testShouldSavePreferredOrganizationForFirstLogin()
    {
        $user         = $this->getUser();
        $organization = $this->getOrganization();
        $repo         = $this->getRepository();

        $user->expects($this->any())->method('getOrganizations')->with($this->identicalTo(true))
            ->willReturn(new ArrayCollection([]));
        $repo->expects($this->once())->method('findOneBy')->with(['user' => $user])->willReturn(false);
        $repo->expects($this->once())->method('savePreferredOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization));
        $this->registry->expects($this->any())->method('getRepository')
            ->with('OroProOrganizationBundle:UserPreferredOrganization')->willReturn($repo);

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn($organization);
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $token->expects($this->never())->method('setOrganizationContext');

        // TODO test notification logic

        $event = new AuthenticationEvent($token);
        $this->assertNull($this->listener->onAuthenticationSuccess($event));
    }

    public function testShouldUpdatePreferredOrganizationIfGuessedAnotherOne()
    {
        $user                  = $this->getUser();
        $organization          = $this->getOrganization(1);
        $preferredOrganization = $this->getOrganization(2);
        $repo                  = $this->getRepository();
        $userPreference        = new UserPreferredOrganization($user, $preferredOrganization);

        $user->expects($this->any())->method('getOrganizations')->with($this->identicalTo(true))
            ->willReturn(new ArrayCollection([$organization]));
        $repo->expects($this->once())->method('findOneBy')->with(['user' => $user])->willReturn($userPreference);
        $repo->expects($this->once())->method('updatePreferredOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization));
        $this->registry->expects($this->any())->method('getRepository')
            ->with('OroProOrganizationBundle:UserPreferredOrganization')->willReturn($repo);

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $token->expects($this->once())->method('getOrganizationContext')->willReturn($organization);
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $token->expects($this->never())->method('setOrganizationContext');

        // TODO test notification logic

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
