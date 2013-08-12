<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Manager\DTO;

use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;

class ItemIdTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $obj = new ItemId('testId', 'testChangeKey');
        $this->assertEquals('testId', $obj->getId());
        $this->assertEquals('testChangeKey', $obj->getChangeKey());
    }

    public function testGettersAndSetters()
    {
        $obj = new ItemId('test', 'test');
        $obj
            ->setId('testId')
            ->setChangeKey('testChangeKey');
        $this->assertEquals('testId', $obj->getId());
        $this->assertEquals('testChangeKey', $obj->getChangeKey());
    }
}
