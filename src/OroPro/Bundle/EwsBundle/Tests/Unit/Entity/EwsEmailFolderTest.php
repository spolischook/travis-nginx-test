<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;

class EwsEmailFolderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $ewsFolder = new EwsEmailFolder();
        ReflectionUtil::setId($ewsFolder, 123);
        $this->assertEquals(123, $ewsFolder->getId());
    }

    public function testEwsIdGetterAndSetter()
    {
        $ewsFolder = new EwsEmailFolder();
        $this->assertNull($ewsFolder->getEwsId());
        $ewsFolder->setEwsId('test');
        $this->assertEquals('test', $ewsFolder->getEwsId());
    }

    public function testEwsChangeKeyGetterAndSetter()
    {
        $ewsFolder = new EwsEmailFolder();
        $this->assertNull($ewsFolder->getEwsChangeKey());
        $ewsFolder->setEwsChangeKey('test');
        $this->assertEquals('test', $ewsFolder->getEwsChangeKey());
    }

    public function testFolderGetterAndSetter()
    {
        $folder = new EmailFolder();

        $ewsFolder = new EwsEmailFolder();
        $this->assertNull($ewsFolder->getFolder());
        $ewsFolder->setFolder($folder);
        $this->assertSame($folder, $ewsFolder->getFolder());
    }
}
