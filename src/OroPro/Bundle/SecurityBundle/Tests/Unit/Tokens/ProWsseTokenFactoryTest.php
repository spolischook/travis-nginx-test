<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Tokens;

use OroPro\Bundle\SecurityBundle\Tokens\ProWsseTokenFactory;

class ProWsseTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new ProWsseTokenFactory();
        $token = $factory->create();

        $this->assertInstanceOf('OroPro\Bundle\SecurityBundle\Tokens\ProWsseToken', $token);
    }
}
