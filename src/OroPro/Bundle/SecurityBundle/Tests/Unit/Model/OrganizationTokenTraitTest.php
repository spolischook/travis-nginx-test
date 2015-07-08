<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Model;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\SecurityBundle\Model\OrganizationTokenTrait;

class OrganizationTokenTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterRolesInOrganizationContext()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OrganizationTokenTrait $organizationTokenTrait */
        $organizationTokenTrait = $this
            ->getObjectForTrait('OroPro\Bundle\SecurityBundle\Model\OrganizationTokenTrait');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Role $role1 */
        $role1 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->disableOriginalConstructor()
            ->setMethods(['getOrganization'])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Role $role2 */
        $role2 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->disableOriginalConstructor()
            ->setMethods(['getOrganization'])
            ->getMock();

        $organization = new Organization();
        $organization->setId(1);

        $organization1 = $organization;
        $organization2 = new Organization();
        $organization2->setId(2);

        $roles = [$role1, $role2];

        $role1->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization1));

        $role2->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization2));

        $result = $organizationTokenTrait->filterRolesInOrganizationContext($organization, $roles);

        $this->assertEquals([$role1], $result);
    }
}
