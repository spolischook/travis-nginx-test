<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Config;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager;
use OroPro\Bundle\OrganizationBundle\Entity\UserOrganization;

class UserOrganizationScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserOrganizationScopeManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $cache          = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $this->securityContext = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->manager = new UserOrganizationScopeManager($this->doctrine, $cache);
        $this->manager->setSecurityContext($this->securityContext);
    }

    public function testGetScopedEntityName()
    {
        $this->assertEquals('organization_user', $this->manager->getScopedEntityName());
    }

    public function testInitializeScopeId()
    {
        $user = new User();
        $user->setId(123);

        $organization = new Organization();
        $organization->setId(456);

        $userOrganization = new UserOrganization($user, $organization);
        $class            = new \ReflectionClass($userOrganization);
        $prop             = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($userOrganization, 789);

        $repo = $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Entity\Repository\UserOrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroProOrganizationBundle:UserOrganization')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroProOrganizationBundle:UserOrganization')
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('getUserOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization))
            ->will($this->returnValue($userOrganization));

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->willReturn($organization);

        $this->assertEquals(789, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForNewOrganization()
    {
        $user = new User();
        $user->setId(123);

        $organization = new Organization();

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->willReturn($organization);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForNewUser()
    {
        $user = new User();

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForUnsupportedUserObject()
    {
        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test user');

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdNoToken()
    {
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testSetScopeId()
    {
        $this->securityContext->expects($this->never())
            ->method('getToken');

        $this->manager->setScopeId(456);
        $this->assertEquals(456, $this->manager->getScopeId());
    }

    public function testSetScopeIdFromEntity()
    {
        $user = new User();
        $user->setId(123);
        $organization = new Organization();
        $organization->setId(456);

        $userOrganization = $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Entity\UserOrganization')
            ->disableOriginalConstructor()
            ->getMock();
        $userOrganization->expects($this->once())->method('getId')->will($this->returnValue(789));

        $repo = $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Entity\Repository\UserOrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroProOrganizationBundle:UserOrganization')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroProOrganizationBundle:UserOrganization')
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('getUserOrganization')
            ->with($this->identicalTo($user), $this->identicalTo($organization))
            ->will($this->returnValue($userOrganization));

        $token = $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->never())
            ->method('getUser');
        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->willReturn($organization);

        $this->manager->setScopeIdFromEntity($user);

        $this->assertEquals(789, $this->manager->getScopeId());
    }
}
