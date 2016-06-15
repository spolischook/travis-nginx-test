<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit\Acl\Voter;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\UserBundle\Helper\UserProHelper;
use OroPro\Bundle\UserBundle\Acl\Voter\RoleVoter;

class RoleVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoleVoter
     */
    protected $roleVoter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserProHelper
     */
    protected $userHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrganizationProHelper
     */
    protected $organizationHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrganizationContextTokenInterface
     */
    protected $token;

    protected function setUp()
    {
        $this->userHelper = $this->getMockBuilder('OroPro\Bundle\UserBundle\Helper\UserProHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organizationHelper = $this->getMockBuilder(
            'OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->token = $this->getMock(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface'
        );
        $this->roleVoter = new RoleVoter($this->userHelper, $this->organizationHelper);
    }

    protected function tearDown()
    {
        unset($this->userHelper, $this->token, $this->roleVoter);
    }

    /**
     * @param $attribute
     * @param $isSupported
     * @dataProvider attributeProvider
     */
    public function testSupportsAttribute($attribute, $isSupported)
    {
        $this->assertEquals($isSupported, $this->roleVoter->supportsAttribute($attribute));
    }

    /**
     * @param $class
     * @param $isSupported
     * @dataProvider classProvider
     */
    public function testSupportsClass($class, $isSupported)
    {
        $this->assertEquals($isSupported, $this->roleVoter->supportsClass($class));
    }

    public function testVoteWhenUserIsAssignedAndLoggedInToGlobalOrganization()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $currentUser */
        $currentUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $currentOrganization */
        $currentOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->setMethods(['getIsGlobal'])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $roleOrganization */
        $roleOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Role $role */
        $role = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->setMethods(['getOrganization'])
            ->getMock();

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($currentUser));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($currentOrganization));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToGlobalOrganization')
            ->will($this->returnValue(true));

        $currentOrganization->expects($this->once())
            ->method('getIsGlobal')
            ->will($this->returnValue(true));

        $result = $this->roleVoter->vote($this->token, $role, ['EDIT']);
        $this->assertEquals(RoleVoter::ACCESS_ABSTAIN, $result);
    }

    public function testVoteGlobalRoleWhenUserIsAssignedButNotLoggedInToGlobalOrganization()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $currentUser */
        $currentUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $currentOrganization */
        $currentOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->setMethods(['getIsGlobal'])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $roleOrganization */
        $roleOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Role $role */
        $role = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->setMethods(['getOrganization'])
            ->getMock();

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($currentUser));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($currentOrganization));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToGlobalOrganization')
            ->will($this->returnValue(true));

        $currentOrganization->expects($this->once())
            ->method('getIsGlobal')
            ->will($this->returnValue(false));

        $role->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));

        $result = $this->roleVoter->vote($this->token, $role, ['EDIT']);
        $this->assertEquals(RoleVoter::ACCESS_ABSTAIN, $result);
    }

    public function testVoteGlobalRoleWhenThereAreNoGlobalOrganization()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $currentUser */
        $currentUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $currentOrganization */
        $currentOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->setMethods(['getIsGlobal'])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Role $role */
        $role = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->setMethods(['getOrganization'])
            ->getMock();

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($currentUser));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($currentOrganization));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToGlobalOrganization')
            ->will($this->returnValue(false));

        $role->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));

        $this->organizationHelper->expects($this->once())
            ->method('isGlobalOrganizationExists')
            ->will($this->returnValue(false));

        $result = $this->roleVoter->vote($this->token, $role, ['EDIT']);
        $this->assertEquals(RoleVoter::ACCESS_ABSTAIN, $result);
    }

    public function testVoteOrganizationRoleWhenUserAssignedToOrganization()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $currentUser */
        $currentUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $currentOrganization */
        $currentOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $roleOrganization */
        $roleOrganization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Role $role */
        $role = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->setMethods(['getOrganization'])
            ->getMock();

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($currentUser));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($currentOrganization));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToGlobalOrganization')
            ->will($this->returnValue(false));

        $role->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($roleOrganization));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToOrganization')
            ->with($roleOrganization, $currentUser)
            ->will($this->returnValue(true));

        $currentOrganization->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $roleOrganization->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $result = $this->roleVoter->vote($this->token, $role, ['EDIT']);
        $this->assertEquals(RoleVoter::ACCESS_ABSTAIN, $result);
    }

    public function classProvider()
    {
        return [
            ['Oro\Bundle\UserBundle\Entity\Role', true],
            ['Oro\Bundle\UserBundle\Entity\User', false]
        ];
    }

    public function attributeProvider()
    {
        return [
            ['VIEW', true],
            ['EDIT', true],
            ['CREATE', false],
            ['DELETE', true],
        ];
    }

    /**
     * @return array
     */
    protected function getOrganizations()
    {
        $organization1 = new GlobalOrganization();
        $organization1->setId(1);
        $organization1->setIsGLobal(false);

        $organization2 = new GlobalOrganization();
        $organization2->setId(2);
        $organization2->setIsGLobal(true);
        return new ArrayCollection([$organization1, $organization2]);
    }
}
