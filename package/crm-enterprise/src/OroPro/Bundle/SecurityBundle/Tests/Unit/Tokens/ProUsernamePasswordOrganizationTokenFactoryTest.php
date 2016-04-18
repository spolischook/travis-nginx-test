<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Tokens;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\SecurityBundle\Tokens\ProUsernamePasswordOrganizationTokenFactory;

class ProUsernamePasswordOrganizationTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $organization = new Organization();
        $factory = new ProUsernamePasswordOrganizationTokenFactory();
        $token = $factory->create('username', 'credentials', 'testProvider', $organization);

        $this->assertInstanceOf('OroPro\Bundle\SecurityBundle\Tokens\ProUsernamePasswordOrganizationToken', $token);
        $this->assertEquals($organization, $token->getOrganizationContext());
        $this->assertEquals('username', $token->getUser());
        $this->assertEquals('credentials', $token->getCredentials());
        $this->assertEquals('testProvider', $token->getProviderKey());
    }
}
