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
     * @dataProvider getEmailsProvider
     */
    public function testGetEmails($user)
    {
        $query = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $folderId = new EwsType\DistinguishedFolderIdType();
        $folderId->Id = 'inbox';
        $sid = null;
        if ($user !== null) {
            $this->manager->selectUser($user);
            $sid = new EwsType\ConnectingSIDType();
            $sid->PrimarySmtpAddress = $user;
        }

        $msg = new EwsType\FindItemResponseMessageType();
        $msg->RootFolder = new EwsType\FindItemParentType();
        $msg->RootFolder->Items = new EwsType\ArrayOfRealItemsType();
        $msg->RootFolder->Items->Message = array();
        $msg->RootFolder->Items->Message[] = new EwsType\MessageType();
        $msg->RootFolder->Items->Message[0]->ItemId = new EwsType\ItemIdType();
        $msg->RootFolder->Items->Message[0]->ItemId->Id = 'Id';
        $msg->RootFolder->Items->Message[0]->ItemId->ChangeKey = 'ChangeKey';
        $msg->RootFolder->Items->Message[0]->Subject = 'Subject';
        $msg->RootFolder->Items->Message[0]->From = new EwsType\SingleRecipientType();
        $msg->RootFolder->Items->Message[0]->From->Mailbox = new EwsType\EmailAddressType();
        $msg->RootFolder->Items->Message[0]->From->Mailbox->EmailAddress = 'fromEmail';
        $msg->RootFolder->Items->Message[0]->DateTimeSent = '2011-06-30 23:59:59';
        $msg->RootFolder->Items->Message[0]->DateTimeReceived = '2012-06-30 23:59:59';
        $msg->RootFolder->Items->Message[0]->DateTimeCreated = '2013-06-30 23:59:59';
        $msg->RootFolder->Items->Message[0]->Importance = 'Normal';
        $msg->RootFolder->Items->Message[0]->InternetMessageId = 'MessageId';
        $msg->RootFolder->Items->Message[0]->ConversationId = new EwsType\ItemIdType();
        $msg->RootFolder->Items->Message[0]->ConversationId->Id = 'ConversationId';
        $msg->RootFolder->Items->Message[0]->ToRecipients = new EwsType\ArrayOfRecipientsType();
        $msg->RootFolder->Items->Message[0]->ToRecipients->Mailbox = array();
        $msg->RootFolder->Items->Message[0]->ToRecipients->Mailbox[] = new EwsType\EmailAddressType();
        $msg->RootFolder->Items->Message[0]->ToRecipients->Mailbox[0]->EmailAddress = 'toEmail';
        $msg->RootFolder->Items->Message[0]->CcRecipients = new EwsType\ArrayOfRecipientsType();
        $msg->RootFolder->Items->Message[0]->CcRecipients->Mailbox = array();
        $msg->RootFolder->Items->Message[0]->CcRecipients->Mailbox[] = new EwsType\EmailAddressType();
        $msg->RootFolder->Items->Message[0]->CcRecipients->Mailbox[0]->EmailAddress = 'ccEmail';
        $msg->RootFolder->Items->Message[0]->BccRecipients = new EwsType\ArrayOfRecipientsType();
        $msg->RootFolder->Items->Message[0]->BccRecipients->Mailbox = array();
        $msg->RootFolder->Items->Message[0]->BccRecipients->Mailbox[] = new EwsType\EmailAddressType();
        $msg->RootFolder->Items->Message[0]->BccRecipients->Mailbox[0]->EmailAddress = 'bccEmail';
        $msg->RootFolder->Items->Message[0]->Attachments = new EwsType\NonEmptyArrayOfAttachmentsType();
        $msg->RootFolder->Items->Message[0]->Attachments->FileAttachment = array();
        $msg->RootFolder->Items->Message[0]->Attachments->FileAttachment[] = new EwsType\FileAttachmentType();
        $msg->RootFolder->Items->Message[0]->Attachments->FileAttachment[0]->AttachmentId =
            new EwsType\AttachmentIdType();
        $msg->RootFolder->Items->Message[0]->Attachments->FileAttachment[0]->AttachmentId->Id = 'attId';

        $this->connector->expects($this->once())
            ->method('findItems')
            ->with(
                $this->equalTo($folderId),
                $this->equalTo($sid),
                $this->identicalTo($query),
                $this->equalTo(EwsType\ItemQueryTraversalType::SHALLOW),
                $this->equalTo(EwsType\DefaultShapeNamesType::DEFAULT_PROPERTIES)
            )
            ->will($this->returnValue(array($msg)));

        $emails = $this->manager->getEmails($query);

        $this->assertCount(1, $emails);

        $email = $emails[0];
        $this->assertEquals('Id', $email->getId()->getId());
        $this->assertEquals('ChangeKey', $email->getId()->getChangeKey());
        $this->assertEquals('Subject', $email->getSubject());
        $this->assertEquals('fromEmail', $email->getFrom());
        $this->assertEquals(new \DateTime('2011-06-30 23:59:59'), $email->getSentAt());
        $this->assertEquals(new \DateTime('2012-06-30 23:59:59'), $email->getReceivedAt());
        $this->assertEquals(new \DateTime('2013-06-30 23:59:59'), $email->getInternalDate());
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

    public static function getEmailsProvider()
    {
        return array(
            //'no user' => array(null),
            'has user' => array('user'),
        );
    }
}

