<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Tokens;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\SecurityBundle\Tokens\ProOrganizationRememberMeTokenFactory;

class ProOrganizationRememberMeTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $user = new User();
        $organization = new Organization();
        $factory = new ProOrganizationRememberMeTokenFactory();
        $token = $factory->create($user, 'testProvider', 'testKey', $organization);

        $this->assertInstanceOf('OroPro\Bundle\SecurityBundle\Tokens\ProOrganizationRememberMeToken', $token);
        $this->assertEquals($user, $token->getUser());
        $this->assertEquals($organization, $token->getOrganizationContext());
        $this->assertEquals('testProvider', $token->getProviderKey());
        $this->assertEquals('testKey', $token->getKey());
    }
}
