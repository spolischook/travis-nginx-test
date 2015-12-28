<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Tokens;

use OroPro\Bundle\SecurityBundle\Tokens\ProOAuthTokenFactory;

class ProOAuthTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new ProOAuthTokenFactory();
        $token = $factory->create('accessToken');

        $this->assertInstanceOf('OroPro\Bundle\SecurityBundle\Tokens\ProOAuthToken', $token);
        $this->assertEquals('accessToken', $token->getAccessToken());
    }
}
