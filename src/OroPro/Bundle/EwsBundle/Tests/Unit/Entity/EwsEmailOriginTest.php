<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;

class EwsEmailOriginTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $origin = new EwsEmailOrigin();
        ReflectionUtil::setId($origin, 123);
        $this->assertEquals(123, $origin->getId());
    }

    public function testServerGetterAndSetter()
    {
        $origin = new EwsEmailOrigin();
        $this->assertNull($origin->getServer());
        $origin->setServer('test');
        $this->assertEquals('test', $origin->getServer());
    }

    public function testUserEmailGetterAndSetter()
    {
        $origin = new EwsEmailOrigin();
        $this->assertNull($origin->getUserEmail());
        $origin->setUserEmail('test');
        $this->assertEquals('test', $origin->getUserEmail());
    }
}
