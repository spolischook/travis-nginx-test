<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\UserBundle\Helper\UserProHelper;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class UserProHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserProHelper
     */
    protected $userHelper;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );
        $this->userHelper = new UserProHelper($this->tokenStorage);
    }

    protected function tearDown()
    {
        unset($this->userHelper);
    }

    /**
     * @dataProvider dataProviderForGlobalOrganization
     */
    public function testIsUserAssignedToGlobalOrganization($user, $expectedUserFromToken, $expected)
    {
        if ($expectedUserFromToken) {
            $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

            $this->tokenStorage->expects($this->once())
                ->method('getToken')
                ->will($this->returnValue($token));

            $token->expects($this->once())
                ->method('getUser')
                ->will($this->returnValue($expectedUserFromToken));
        } else {
            $this->tokenStorage->expects($this->never())->method($this->anything());
        }

        $result = $this->userHelper->isUserAssignedToGlobalOrganization($user);
        $this->assertEquals($expected, $result);
    }

    public function dataProviderForGlobalOrganization()
    {
        $globalOrganization = new GlobalOrganization();
        $globalOrganization->setIsGLobal(true);

        $notGlobalOrganization = new GlobalOrganization();
        $notGlobalOrganization->setIsGLobal(false);

        $userAssignedToGlobalOrganization = new User();
        $userAssignedToGlobalOrganization->setOrganizations(
            new ArrayCollection([$globalOrganization, $notGlobalOrganization])
        );


        $userNotAssignedToGlobalOrganization = new User();
        $userNotAssignedToGlobalOrganization->setOrganizations(
            new ArrayCollection([$notGlobalOrganization])
        );

        return [
            [$userAssignedToGlobalOrganization, null, true],
            [$userNotAssignedToGlobalOrganization, null, false],
            [null, $userAssignedToGlobalOrganization, true],
            [null, $userNotAssignedToGlobalOrganization, false],
        ];
    }

    /**
     * @dataProvider dataProviderForOrganization
     */
    public function testIsUserAssignedToOrganization($organization, $user, $expectedUserFromToken, $expected)
    {
        if ($expectedUserFromToken) {
            $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

            $this->tokenStorage->expects($this->once())
                ->method('getToken')
                ->will($this->returnValue($token));

            $token->expects($this->once())
                ->method('getUser')
                ->will($this->returnValue($expectedUserFromToken));
        } else {
            $this->tokenStorage->expects($this->never())->method($this->anything());
        }

        $result = $this->userHelper->isUserAssignedToOrganization($organization, $user);
        $this->assertEquals($expected, $result);
    }

    public function dataProviderForOrganization()
    {
        $organization1 = new Organization();
        $organization1->setId(1);

        $organization2 = new Organization();
        $organization2->setId(2);

        $organization3 = new Organization();
        $organization3->setId(3);

        $user = new User();
        $user->setOrganizations(
            new ArrayCollection([$organization1, $organization2])
        );

        return [
            [$organization1, $user, null, true],
            [$organization3, $user, null, false],
            [$organization1, null, $user, true],
            [$organization3, null, $user, false],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Security token in token storage must exist.
     */
    public function testIsUserAssignedToOrganizationFailedWhenThereIsNoTokenInStorage()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null));
        $this->userHelper->isUserAssignedToOrganization(new Organization());
    }

    /**
     * @dataProvider dataProviderForInvalidTokensUser
     */
    public function testIsUserAssignedToOrganizationFailedWhenTokensUserIsIncorrect(
        $invalidUser,
        $expectedExceptionMessage
    ) {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($invalidUser));

        $this->setExpectedException('RuntimeException', $expectedExceptionMessage);

        $this->userHelper->isUserAssignedToOrganization(new Organization());
    }

    /**
     * @dataProvider dataProviderForInvalidTokensUser
     */
    public function testIsUserAssignedToGlobalOrganizationFailedWhenTokensUserIsIncorrect(
        $invalidUser,
        $expectedExceptionMessage
    ) {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($invalidUser));

        $this->setExpectedException('RuntimeException', $expectedExceptionMessage);

        $this->userHelper->isUserAssignedToGlobalOrganization();
    }

    public function dataProviderForInvalidTokensUser()
    {
        return [
            [
                'username',
                'Security token must return a user object instance of Oro\Bundle\UserBundle\Entity\User, ' .
                'string is given.'
            ],
            [
                new \stdClass(),
                'Security token must return a user object instance of Oro\Bundle\UserBundle\Entity\User, ' .
                'stdClass is given.'
            ],
            [
                null,
                'Security token must return a user object instance of Oro\Bundle\UserBundle\Entity\User, ' .
                'NULL is given.'
            ],
        ];
    }
}
