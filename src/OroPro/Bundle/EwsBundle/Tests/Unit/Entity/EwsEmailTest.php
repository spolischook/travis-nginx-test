<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;

class EwsEmailTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $ewsEmail = new EwsEmail();
        ReflectionUtil::setId($ewsEmail, 123);
        $this->assertEquals(123, $ewsEmail->getId());
    }

    public function testEwsIdGetterAndSetter()
    {
        $ewsEmail = new EwsEmail();
        $this->assertNull($ewsEmail->getEwsId());
        $ewsEmail->setEwsId('test');
        $this->assertEquals('test', $ewsEmail->getEwsId());
    }

    public function testEwsChangeKeyGetterAndSetter()
    {
        $ewsEmail = new EwsEmail();
        $this->assertNull($ewsEmail->getEwsChangeKey());
        $ewsEmail->setEwsChangeKey('test');
        $this->assertEquals('test', $ewsEmail->getEwsChangeKey());
    }

    public function testEmailGetterAndSetter()
    {
        $email = new Email();

        $ewsEmail = new EwsEmail();
        $this->assertNull($ewsEmail->getEmail());
        $ewsEmail->setEmail($email);
        $this->assertSame($email, $ewsEmail->getEmail());
    }

    public function testEwsFolderGetterAndSetter()
    {
        $folder = new EwsEmailFolder();

        $ewsEmail = new EwsEmail();
        $this->assertNull($ewsEmail->getEwsFolder());
        $ewsEmail->setEwsFolder($folder);
        $this->assertSame($folder, $ewsEmail->getEwsFolder());
    }
}
