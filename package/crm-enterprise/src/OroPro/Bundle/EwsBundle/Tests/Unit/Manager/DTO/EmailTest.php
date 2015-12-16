<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Manager\DTO;

use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;

class EmailTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $manager = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\EwsEmailManager')
            ->disableOriginalConstructor()
            ->getMock();

        $id = new ItemId('testId', 'testChangeKey');
        $sentAt = new \DateTime('now');
        $receivedAt = new \DateTime('now');
        $internalDate = new \DateTime('now');
        $seen = true;

        $obj = new Email($manager);
        $obj
            ->setId($id)
            ->setSeen($seen)
            ->setSubject('testSubject')
            ->setFrom('testFrom')
            ->addToRecipient('testToRecipient')
            ->addCcRecipient('testCcRecipient')
            ->addBccRecipient('testBccRecipient')
            ->setSentAt($sentAt)
            ->setReceivedAt($receivedAt)
            ->setInternalDate($internalDate)
            ->setImportance(1)
            ->setMessageId('testMessageId')
            ->setXMessageId('testXMessageId')
            ->setXThreadId('testXThreadId')
            ->addAttachmentId('attId');

        $this->assertEquals($id, $obj->getId());
        $this->assertEquals('testSubject', $obj->getSubject());
        $this->assertEquals('testFrom', $obj->getFrom());
        $toRecipients = $obj->getToRecipients();
        $this->assertEquals('testToRecipient', $toRecipients[0]);
        $ccRecipients = $obj->getCcRecipients();
        $this->assertEquals('testCcRecipient', $ccRecipients[0]);
        $bccRecipients = $obj->getBccRecipients();
        $this->assertEquals('testBccRecipient', $bccRecipients[0]);
        $this->assertEquals($sentAt, $obj->getSentAt());
        $this->assertEquals($receivedAt, $obj->getReceivedAt());
        $this->assertEquals($internalDate, $obj->getInternalDate());
        $this->assertEquals(1, $obj->getImportance());
        $this->assertEquals('testMessageId', $obj->getMessageId());
        $this->assertEquals('testXMessageId', $obj->getXMessageId());
        $this->assertEquals('testXThreadId', $obj->getXThreadId());
        $attIds = $obj->getAttachmentIds();
        $this->assertEquals('attId', $attIds[0]);
        $this->assertTrue($obj->isSeen());

        $body = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\DTO\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('getEmailBody')
            ->with($this->equalTo($id))
            ->will($this->returnValue($body));
        $this->assertTrue($body === $obj->getBody());

        $attachment = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Manager\DTO\EmailAttachment')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('getEmailAttachments')
            ->with($this->equalTo(array('attId')))
            ->will($this->returnValue(array($attachment)));
        $attachments = $obj->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertTrue($attachment === $attachments[0]);
    }
}
