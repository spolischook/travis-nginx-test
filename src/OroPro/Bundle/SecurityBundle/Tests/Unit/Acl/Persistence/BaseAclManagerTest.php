<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use OroPro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Acl\Persistence\BaseAclManager;

class BaseAclManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AbstractAclManager */
    private $manager;

    protected function setUp()
    {
        $this->manager = new BaseAclManager();
    }

    public function testGetSid()
    {
        $this->assertEquals(
            new RoleSecurityIdentity('ROLE_TEST'),
            $this->manager->getSid('ROLE_TEST')
        );

        $src = $this->getMock('Symfony\Component\Security\Core\Role\RoleInterface');
        $src->expects($this->once())
            ->method('getRole')
            ->will($this->returnValue('ROLE_TEST'));
        $this->assertEquals(
            new RoleSecurityIdentity('ROLE_TEST'),
            $this->manager->getSid($src)
        );

        $src = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $src->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('Test'));
        $this->assertEquals(
            new UserSecurityIdentity('Test', get_class($src)),
            $this->manager->getSid($src)
        );

        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('Test'));
        $src = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $src->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->assertEquals(
            new UserSecurityIdentity('Test', get_class($user)),
            $this->manager->getSid($src)
        );

        $businessUnit = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface');
        $businessUnit->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->assertEquals(
            new BusinessUnitSecurityIdentity(1, get_class($businessUnit)),
            $this->manager->getSid($businessUnit)
        );

        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $organization->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->assertEquals(
            new OrganizationSecurityIdentity(1, get_class($organization)),
            $this->manager->getSid($organization)
        );

        $this->setExpectedException('\InvalidArgumentException');
        $this->manager->getSid(new \stdClass());
    }
}
