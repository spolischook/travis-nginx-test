<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\UserBundle\Helper\UserProHelper;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class UserProHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserProHelper
     */
    protected $userHelper;

    protected function setUp()
    {
        $this->userHelper = new UserProHelper();
    }

    protected function tearDown()
    {
        unset($this->userHelper);
    }

    /**
     * @dataProvider dataProviderForSystemOrganization
     */
    public function testIsUserAssignedToSystemOrganization($user, $expected)
    {
        $result = $this->userHelper->isUserAssignedToSystemOrganization($user, $expected);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataProviderForOrganization
     */
    public function testIsUserAssignedToOrganization($user, $organization, $expected)
    {
        $result = $this->userHelper->isUserAssignedToOrganization($user, $organization);
        $this->assertEquals($expected, $result);
    }

    public function dataProviderForSystemOrganization()
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
            [$userAssignedToGlobalOrganization, true],
            [$userNotAssignedToGlobalOrganization, false]
        ];
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
            [$user, $organization1, true],
            [$user, $organization3, false]
        ];
    }
}
