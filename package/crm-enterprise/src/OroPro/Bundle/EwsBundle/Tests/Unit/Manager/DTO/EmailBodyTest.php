<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Manager\DTO;

use OroPro\Bundle\EwsBundle\Manager\DTO\EmailBody;

class EmailBodyTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $obj = new EmailBody();
        $obj
            ->setContent('testContent')
            ->setBodyIsText(true);
        $this->assertEquals('testContent', $obj->getContent());
        $this->assertTrue($obj->getBodyIsText());
    }
}
