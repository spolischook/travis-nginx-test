<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Manager\DTO;

use OroPro\Bundle\EwsBundle\Connector\EwsAdditionalPropertiesBuilder;
use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

class EwsEmailManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSelectFolder()
    {
        $connector = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\EwsConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $manager = new EwsEmailManager($connector);

        $this->assertEquals('inbox', $manager->getSelectedFolder());
        $manager->selectFolder('test');
        $this->assertEquals('test', $manager->getSelectedFolder());
    }

    public function testSelectUser()
    {
        $connector = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\EwsConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $manager = new EwsEmailManager($connector);

        $this->assertNull($manager->getSelectedUser());
        $manager->selectUser('test');
        $this->assertEquals('test', $manager->getSelectedUser());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetEmails()
    {
        $ewsMock = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->disableOriginalConstructor()
            ->setMethods(array('FindItem', 'GetItem', 'GetAttachment'))
            ->getMock();

        $connector = new EwsConnector($ewsMock);
        $manager = new EwsEmailManager($connector);

        $query = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $folderId = new EwsType\DistinguishedFolderIdType();
        $folderId->Id = EwsType\DistinguishedFolderIdNameType::INBOX;

        $findMsg = new EwsType\FindItemResponseMessageType();
        $findMsg->RootFolder = new EwsType\FindItemParentType();
        $findMsg->RootFolder->Items = new EwsType\ArrayOfRealItemsType();
        $findMsg->RootFolder->Items->Message = array();
        $findMsg->RootFolder->Items->Message[] = new EwsType\MessageType();
        $findMsg->RootFolder->Items->Message[0]->ItemId = new EwsType\ItemIdType();
        $findMsg->RootFolder->Items->Message[0]->ItemId->Id = 'Id';
        $findMsg->RootFolder->Items->Message[0]->ItemId->ChangeKey = 'ChangeKey';
        $findResponse = new EwsType\FindItemResponseType();
        $findResponse->ResponseMessages = new EwsType\ArrayOfResponseMessagesType();
        $findResponse->ResponseMessages->FindItemResponseMessage = [];
        $findResponse->ResponseMessages->FindItemResponseMessage[] = $findMsg;

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
        $msgResponse = new EwsType\GetItemResponseType();
        $msgResponse->ResponseMessages = new EwsType\ArrayOfResponseMessagesType();
        $msgResponse->ResponseMessages->GetItemResponseMessage = [];
        $msgResponse->ResponseMessages->GetItemResponseMessage[] = $msg;

        $bodyMsg = new EwsType\ItemInfoResponseMessageType();
        $bodyMsg->Items = new EwsType\ArrayOfRealItemsType();
        $bodyMsg->Items->Message = array();
        $bodyMsg->Items->Message[] = new EwsType\MessageType();
        $bodyMsg->Items->Message[0]->Body = new EwsType\BodyType();
        $bodyMsg->Items->Message[0]->Body->_ = 'bodyContent';
        $bodyMsg->Items->Message[0]->Body->BodyType = 'HTML';
        $bodyMsgResponse = new EwsType\GetItemResponseType();
        $bodyMsgResponse->ResponseMessages = new EwsType\ArrayOfResponseMessagesType();
        $bodyMsgResponse->ResponseMessages->GetItemResponseMessage = [];
        $bodyMsgResponse->ResponseMessages->GetItemResponseMessage[] = $bodyMsg;

        $attMsg = new EwsType\AttachmentInfoResponseMessageType();
        $attMsg->Attachments = new EwsType\ArrayOfAttachmentsType();
        $attMsg->Attachments->FileAttachment = array();
        $attMsg->Attachments->FileAttachment[] = new EwsType\FileAttachmentType();
        $attMsg->Attachments->FileAttachment[0]->Content = 'attContent';
        $attMsg->Attachments->FileAttachment[0]->ContentType = 'attContentType';
        $attMsg->Attachments->FileAttachment[0]->Name = 'file';
        $attMsgResponse = new EwsType\GetAttachmentResponseType();
        $attMsgResponse->ResponseMessages = new EwsType\ArrayOfResponseMessagesType();
        $attMsgResponse->ResponseMessages->GetAttachmentResponseMessage = [];
        $attMsgResponse->ResponseMessages->GetAttachmentResponseMessage[] = $attMsg;

        $findItemRequest = new EwsType\FindItemType();
        $findItemRequest->ItemShape = new EwsType\ItemResponseShapeType();
        $findItemRequest->ItemShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $findItemRequest->Traversal = EwsType\ItemQueryTraversalType::SHALLOW;
        $findItemRequest->ParentFolderIds = new EwsType\NonEmptyArrayOfBaseFolderIdsType();
        $findItemRequest->ParentFolderIds->DistinguishedFolderId = [];
        $findItemRequest->ParentFolderIds->DistinguishedFolderId[] = new EwsType\DistinguishedFolderIdType();
        $findItemRequest->ParentFolderIds->DistinguishedFolderId[0]->Id = EwsType\DistinguishedFolderIdNameType::INBOX;

        $msgRequest = new EwsType\GetItemType();
        $msgRequest->ItemShape = new EwsType\ItemResponseShapeType();
        $msgRequest->ItemShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $msgRequest->ItemShape->BodyType = EwsType\BodyTypeResponseType::BEST;
        $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();
        $additionalPropertiesBuilder->addUnindexedFieldUris(
            [
                EwsType\UnindexedFieldURIType::MESSAGE_FROM,
                EwsType\UnindexedFieldURIType::MESSAGE_TO_RECIPIENTS,
                EwsType\UnindexedFieldURIType::MESSAGE_CC_RECIPIENTS,
                EwsType\UnindexedFieldURIType::MESSAGE_BCC_RECIPIENTS,
                EwsType\UnindexedFieldURIType::ITEM_SUBJECT,
                EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_SENT,
                EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_RECEIVED,
                EwsType\UnindexedFieldURIType::ITEM_DATE_TIME_CREATED,
                EwsType\UnindexedFieldURIType::ITEM_IMPORTANCE,
                EwsType\UnindexedFieldURIType::MESSAGE_INTERNET_MESSAGE_ID,
                EwsType\UnindexedFieldURIType::ITEM_CONVERSATION_ID,
            ]
        );
        $msgRequest->ItemShape->AdditionalProperties = $additionalPropertiesBuilder->get();
        $msgRequest->ItemIds = new EwsType\NonEmptyArrayOfBaseItemIdsType();
        $msgRequest->ItemIds->ItemId = [];
        $msgRequest->ItemIds->ItemId[] = $msg->Items->Message[0]->ItemId;

        $bodyMsgRequest = new EwsType\GetItemType();
        $bodyMsgRequest->ItemShape = new EwsType\ItemResponseShapeType();
        $bodyMsgRequest->ItemShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $bodyMsgRequest->ItemShape->BodyType = EwsType\BodyTypeResponseType::BEST;
        $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();
        $additionalPropertiesBuilder->addUnindexedFieldUris(
            [
                EwsType\UnindexedFieldURIType::ITEM_BODY,
            ]
        );
        $bodyMsgRequest->ItemShape->AdditionalProperties = $additionalPropertiesBuilder->get();
        $bodyMsgRequest->ItemIds = new EwsType\NonEmptyArrayOfBaseItemIdsType();
        $bodyMsgRequest->ItemIds->ItemId = [];
        $bodyMsgRequest->ItemIds->ItemId[] = $msg->Items->Message[0]->ItemId;

        $attMsgRequest = new EwsType\GetAttachmentType();
        $attMsgRequest->AttachmentShape = new EwsType\AttachmentResponseShapeType();
        $attMsgRequest->AttachmentShape->IncludeMimeContent = false;
        $attMsgRequest->AttachmentShape->FilterHtmlContent = false;
        $attMsgRequest->AttachmentShape->BodyType = EwsType\BodyTypeResponseType::BEST;
        $attMsgRequest->AttachmentIds = new EwsType\NonEmptyArrayOfRequestAttachmentIdsType();
        $attMsgRequest->AttachmentIds->AttachmentId = [];
        $attMsgRequest->AttachmentIds->AttachmentId[0] = new EwsType\RequestAttachmentIdType();
        $attMsgRequest->AttachmentIds->AttachmentId[0]->Id = 'attId';

        $ewsMock->expects($this->at(0))
            ->method('FindItem')
            ->with($this->equalTo($findItemRequest))
            ->will($this->returnValue($findResponse));
        $ewsMock->expects($this->at(1))
            ->method('GetItem')
            ->with($msgRequest)
            ->will($this->returnValue($msgResponse));
        $emailId = new EwsType\ItemIdType();
        $emailId->Id = 'Id';
        $emailId->ChangeKey = 'ChangeKey';
        $ewsMock->expects($this->at(2))
            ->method('GetItem')
            ->with($bodyMsgRequest)
            ->will($this->returnValue($bodyMsgResponse));
        $ewsMock->expects($this->at(3))
            ->method('GetAttachment')
            ->with($attMsgRequest)
            ->will($this->returnValue($attMsgResponse));

        $emails = $manager->getEmails($query);

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

        $body = $email->getBody();

        $this->assertEquals('bodyContent', $body->getContent());
        $this->assertFalse($body->getBodyIsText());

        $attachments = $email->getAttachments();

        $this->assertCount(1, $attachments);
        $this->assertEquals('file', $attachments[0]->getFileName());
        $this->assertEquals('attContent', $attachments[0]->getContent());
        $this->assertEquals('attContentType', $attachments[0]->getContentType());
        $this->assertEquals('BINARY', $attachments[0]->getContentTransferEncoding());
    }
}
