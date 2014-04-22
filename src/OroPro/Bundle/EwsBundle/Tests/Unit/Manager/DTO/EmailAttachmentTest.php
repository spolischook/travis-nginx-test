<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Manager\DTO;

use OroPro\Bundle\EwsBundle\Manager\DTO\EmailAttachment;

class EmailAttachmentTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $obj = new EmailAttachment();
        $obj
            ->setFileName('testFileName')
            ->setContentType('testContentType')
            ->setContentTransferEncoding('testContentTransferEncoding')
            ->setContent('testContent');
        $this->assertEquals('testFileName', $obj->getFileName());
        $this->assertEquals('testContentType', $obj->getContentType());
        $this->assertEquals('testContentTransferEncoding', $obj->getContentTransferEncoding());
        $this->assertEquals('testContent', $obj->getContent());
    }
}
