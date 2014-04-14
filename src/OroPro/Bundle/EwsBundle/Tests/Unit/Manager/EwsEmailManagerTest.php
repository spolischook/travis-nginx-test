<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Manager\DTO;

use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

class EwsEmailManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EwsEmailManager */
    private $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $connector;

    protected function setUp()
    {
        $this->connector = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\EwsConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new EwsEmailManager($this->connector);
    }

    public function testSelectFolder()
    {
        $this->assertEquals('inbox', $this->manager->getSelectedFolder());
        $this->manager->selectFolder('test');
        $this->assertEquals('test', $this->manager->getSelectedFolder());
    }

    public function testSelectUser()
    {
        $this->assertNull($this->manager->getSelectedUser());
        $this->manager->selectUser('test');
        $this->assertEquals('test', $this->manager->getSelectedUser());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetEmails()
    {
        $query = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $folderId = new EwsType\DistinguishedFolderIdType();
        $folderId->Id = 'inbox';

        $findMsg = new EwsType\FindItemResponseMessageType();
        $findMsg->RootFolder = new EwsType\FindItemParentType();
        $findMsg->RootFolder->Items = new EwsType\ArrayOfRealItemsType();
        $findMsg->RootFolder->Items->Message = array();
        $findMsg->RootFolder->Items->Message[] = new EwsType\MessageType();
        $findMsg->RootFolder->Items->Message[0]->ItemId = new EwsType\ItemIdType();
        $findMsg->RootFolder->Items->Message[0]->ItemId->Id = 'Id';
        $findMsg->RootFolder->Items->Message[0]->ItemId->ChangeKey = 'ChangeKey';

        $msg = new EwsType\ItemInfoResponseMessageType();
        $msg->Items = new EwsType\ArrayOfRealItemsType();
        $msg->Items->Message = array();
        $msg->Items->Message[] = new EwsType\MessageType();
        $msg->Items->Message[0]->ItemId = new EwsType\ItemIdType();
        $msg->Items->Message[0]->ItemId->Id = 'Id';
        $msg->Items->Message[0]->ItemId->ChangeKey = 'ChangeKey';
        $msg->Items->Message[0]->Subject = 'Subject';
        $msg->Items->Message[0]->From = new EwsType\SingleRecipientType();
        $msg->Items->Message[0]->From->Mailbox = new EwsType\EmailAddressType();
        $msg->Items->Message[0]->From->Mailbox->EmailAddress = 'fromEmail';
        $msg->Items->Message[0]->DateTimeSent = '2011-06-30 23:59:59 +0';
        $msg->Items->Message[0]->DateTimeReceived = '2012-06-30 23:59:59 +0';
        $msg->Items->Message[0]->DateTimeCreated = '2013-06-30 23:59:59 +0';
        $msg->Items->Message[0]->Importance = 'Normal';
        $msg->Items->Message[0]->InternetMessageId = 'MessageId';
        $msg->Items->Message[0]->ConversationId = new EwsType\ItemIdType();
        $msg->Items->Message[0]->ConversationId->Id = 'ConversationId';
        $msg->Items->Message[0]->ToRecipients = new EwsType\ArrayOfRecipientsType();
        $msg->Items->Message[0]->ToRecipients->Mailbox = array();
        $msg->Items->Message[0]->ToRecipients->Mailbox[] = new EwsType\EmailAddressType();
        $msg->Items->Message[0]->ToRecipients->Mailbox[0]->EmailAddress = 'toEmail';
        $msg->Items->Message[0]->CcRecipients = new EwsType\ArrayOfRecipientsType();
        $msg->Items->Message[0]->CcRecipients->Mailbox = array();
        $msg->Items->Message[0]->CcRecipients->Mailbox[] = new EwsType\EmailAddressType();
        $msg->Items->Message[0]->CcRecipients->Mailbox[0]->EmailAddress = 'ccEmail';
        $msg->Items->Message[0]->BccRecipients = new EwsType\ArrayOfRecipientsType();
        $msg->Items->Message[0]->BccRecipients->Mailbox = array();
        $msg->Items->Message[0]->BccRecipients->Mailbox[] = new EwsType\EmailAddressType();
        $msg->Items->Message[0]->BccRecipients->Mailbox[0]->EmailAddress = 'bccEmail';
        $msg->Items->Message[0]->Attachments = new EwsType\NonEmptyArrayOfAttachmentsType();
        $msg->Items->Message[0]->Attachments->FileAttachment = array();
        $msg->Items->Message[0]->Attachments->FileAttachment[] = new EwsType\FileAttachmentType();
        $msg->Items->Message[0]->Attachments->FileAttachment[0]->AttachmentId =
            new EwsType\AttachmentIdType();
        $msg->Items->Message[0]->Attachments->FileAttachment[0]->AttachmentId->Id = 'attId';

        $this->connector->expects($this->once())
            ->method('findItems')
            ->with(
                $this->equalTo($folderId),
                $this->identicalTo($query)
            )
            ->will($this->returnValue(array($findMsg)));
        $this->connector->expects($this->once())
            ->method('getItems')
            ->with(
                $this->equalTo(array($findMsg->RootFolder->Items->Message[0]->ItemId))
            )
            ->will($this->returnValue(array($msg)));

        $emails = $this->manager->getEmails($query);

        $this->assertCount(1, $emails);

        $email = $emails[0];
        $this->assertEquals('Id', $email->getId()->getId());
        $this->assertEquals('ChangeKey', $email->getId()->getChangeKey());
        $this->assertEquals('Subject', $email->getSubject());
        $this->assertEquals('fromEmail', $email->getFrom());
        $this->assertEquals(
            new \DateTime('2011-06-30 23:59:59', new \DateTimeZone('UTC')),
            $email->getSentAt()
        );
        $this->assertEquals(
            new \DateTime('2012-06-30 23:59:59', new \DateTimeZone('UTC')),
            $email->getReceivedAt()
        );
        $this->assertEquals(
            new \DateTime('2013-06-30 23:59:59', new \DateTimeZone('UTC')),
            $email->getInternalDate()
        );
        $this->assertEquals(0, $email->getImportance());
        $this->assertEquals('MessageId', $email->getMessageId());
        $this->assertEquals('Id', $email->getXMessageId());
        $this->assertEquals('ConversationId', $email->getXThreadId());
        $toRecipients = $email->getToRecipients();
        $this->assertEquals('toEmail', $toRecipients[0]);
        $ccRecipients = $email->getCcRecipients();
        $this->assertEquals('ccEmail', $ccRecipients[0]);
        $bccRecipients = $email->getBccRecipients();
        $this->assertEquals('bccEmail', $bccRecipients[0]);
        $attachmentIds = $email->getAttachmentIds();
        $this->assertEquals('attId', $attachmentIds[0]);

        $bodyMsg = new EwsType\MessageType();
        $bodyMsg->Body = new EwsType\BodyType();
        $bodyMsg->Body->_ = 'bodyContent';
        $bodyMsg->Body->BodyType = 'HTML';

        $emailId = new EwsType\ItemIdType();
        $emailId->Id = 'Id';
        $emailId->ChangeKey = 'ChangeKey';
        $this->connector->expects($this->once())
            ->method('getItem')
            ->with(
                $this->equalTo($emailId),
                $this->equalTo(EwsType\DefaultShapeNamesType::DEFAULT_PROPERTIES),
                $this->equalTo(EwsType\BodyTypeResponseType::BEST)
            )
            ->will($this->returnValue($bodyMsg));

        $body = $email->getBody();

        $this->assertEquals('bodyContent', $body->getContent());
        $this->assertFalse($body->getBodyIsText());

        $attMsg = new EwsType\AttachmentInfoResponseMessageType();
        $attMsg->Attachments = new EwsType\ArrayOfAttachmentsType();
        $attMsg->Attachments->FileAttachment = array();
        $attMsg->Attachments->FileAttachment[] = new EwsType\FileAttachmentType();
        $attMsg->Attachments->FileAttachment[0]->Content = 'attContent';
        $attMsg->Attachments->FileAttachment[0]->ContentType = 'attContentType';
        $attMsg->Attachments->FileAttachment[0]->Name = 'file';

        $attId = new EwsType\RequestAttachmentIdType();
        $attId->Id = 'attId';
        $this->connector->expects($this->once())
            ->method('getAttachments')
            ->with(
                $this->equalTo(array($attId)),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(EwsType\BodyTypeResponseType::BEST)
            )
            ->will($this->returnValue(array($attMsg)));

        $attachments = $email->getAttachments();

        $this->assertCount(1, $attachments);
        $this->assertEquals('file', $attachments[0]->getFileName());
        $this->assertEquals('attContent', $attachments[0]->getContent());
        $this->assertEquals('attContentType', $attachments[0]->getContentType());
        $this->assertEquals('BINARY', $attachments[0]->getContentTransferEncoding());
    }
}
