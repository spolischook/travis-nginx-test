<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Config;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager;
use OroPro\Bundle\OrganizationBundle\Entity\UserOrganization;

class UserOrganizationScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetScopedEntityName()
    {
        $object = new UserOrganizationScopeManager($this->getMock('Doctrine\Common\Persistence\ObjectManager'));
        $this->assertEquals(UserOrganizationScopeManager::SCOPED_ENTITY_NAME, $object->getScopedEntityName());
    }

    public function testSetSecurity()
    {
        $group1 = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        $group1->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $group2 = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        $group2->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $user = new User();
        $user->setId(1)
            ->addGroup($group1)
            ->addGroup($group2);
        $organization = new Organization();
        $organization->setId(1);
        $userOrganization = new UserOrganization($user, $organization);
        $class = new \ReflectionClass($userOrganization);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($userOrganization, 3);

        $repo = $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Entity\Repository\UserOrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getUserOrganization')
            ->will($this->returnValue($userOrganization));


        $token = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $om->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $object = $this->getMock(
            'OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager',
            ['loadStoredSettings'],
            [$om]
        );
        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $security->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $object->setSecurity($security);
    }

    public function testSetScopeId()
    {
        $repo = $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Entity\Repository\UserOrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $group1 = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        $group1->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $group2 = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        $group2->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $user = new User();
        $user->setId(1)
            ->addGroup($group1)
            ->addGroup($group2);
        $organization = new Organization();
        $organization->setId(1);
        $userOrganization = new UserOrganization($user, $organization);
        $class = new \ReflectionClass($userOrganization);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($userOrganization, 3);
        $repo->expects($this->any())
            ->method('getUserOrganization')
            ->will($this->returnValue($userOrganization));

        $token = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $om->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $object = new UserOrganizationScopeManager($om);
        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $security->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $object->setSecurity($security);
        $object->setScopeId();
        $this->assertEquals($userOrganization->getId(), $object->getScopeId());
    }
}
