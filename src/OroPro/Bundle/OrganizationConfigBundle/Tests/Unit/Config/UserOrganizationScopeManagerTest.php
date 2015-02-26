<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Config;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;
use OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager;

class UserOrganizationScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetScopedEntityName()
    {
        $object = new UserOrganizationScopeManager($this->getMock('Doctrine\Common\Persistence\ObjectManager'));
        $this->assertEquals(UserOrganizationScopeManager::SCOPED_ENTITY_NAME, $object->getScopedEntityName());
    }

    public function testSetSecurity()
    {
        $repo = $this->getMockBuilder(
            'OroPro\Bundle\OrganizationBundle\Entity\Repository\UserPreferredOrganizationRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getPreferredOrganization')
            ->will($this->returnValue(null));
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
        $repo = $this->getMockBuilder(
            'OroPro\Bundle\OrganizationBundle\Entity\Repository\UserPreferredOrganizationRepository'
        )
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
        $preferredOrganization = new UserPreferredOrganization($user, $organization);
        $class = new \ReflectionClass($preferredOrganization);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $repo->expects($this->once())
            ->method('getPreferredOrganization')
            ->will($this->returnValue($preferredOrganization));
        $prop->setValue($preferredOrganization, 4);

        $token = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->exactly(2))
            ->method('getUser')
            ->will($this->returnValue($user));
        $token->expects($this->exactly(2))
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $om->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $object = new UserOrganizationScopeManager($om);
        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $security->expects($this->exactly(2))
            ->method('getToken')
            ->will($this->returnValue($token));

        $object->setSecurity($security);
        $object->setScopeId();
        $this->assertEquals(4, $object->getScopeId());
    }
}
