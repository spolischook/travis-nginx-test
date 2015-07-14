<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit\Acl\Voter;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
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
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    protected $token;

    protected function setUp()
    {
        $this->userHelper = $this->getMockBuilder('OroPro\Bundle\UserBundle\Helper\UserProHelper')
            ->getMock();
        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->roleVoter = new RoleVoter($this->userHelper);
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

    public function testVoteWhenUserIsAssignedToSystemOrganization()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $user */
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->getMock();

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToSystemOrganization')
            ->with($user)
            ->will($this->returnValue(true));

        $role = new Role();

        $result = $this->roleVoter->vote($this->token, $role, ['EDIT']);
        $this->assertEquals(RoleVoter::ACCESS_GRANTED, $result);
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
