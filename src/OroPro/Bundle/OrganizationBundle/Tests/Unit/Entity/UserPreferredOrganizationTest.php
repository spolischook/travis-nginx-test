<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Entity;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;

class UserPreferredOrganizationTest extends \PHPUnit_Framework_TestCase
{
    /** @var User|\PHPUnit_Framework_MockObject_MockObject */
    protected $user;

    /** @var Organization|\PHPUnit_Framework_MockObject_MockObject */
    protected $organization;

    protected function setUp()
    {
        $this->user         = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();
        $this->organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        unset($this->user, $this->organization);
    }

    public function testConstructorShouldSetParams()
    {
        $entity = new UserPreferredOrganization($this->user, $this->organization);

        $this->assertSame($this->user, $entity->getUser());
        $this->assertSame($this->organization, $entity->getOrganization());
    }

    public function testEmptyIdentifierAfterConstruction()
    {
        $entity = new UserPreferredOrganization($this->user, $this->organization);

        $this->assertNull($entity->getId());
    }
}
