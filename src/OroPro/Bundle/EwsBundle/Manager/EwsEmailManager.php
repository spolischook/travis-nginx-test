<?php

namespace OroPro\Bundle\EwsBundle\Manager;

use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Manager\DTO\EmailAttachment;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\DTO\EmailBody;

class EwsEmailManager
{
    /**
     * The list of special folder names, also known as EWS distinguished folders
     *
     * @var array
     */
    protected static $distinguishedFolderNames = array(
        'inbox' => EwsType\DistinguishedFolderIdNameType::INBOX,
        'sent' => EwsType\DistinguishedFolderIdNameType::OUTBOX,
        'drafts' => EwsType\DistinguishedFolderIdNameType::DRAFTS,
        'trash' => EwsType\DistinguishedFolderIdNameType::DELETEDITEMS,
    );

    /**
     * @var EwsConnector
     */
    protected $connector;

    /**
     * A mailbox name all email related actions are performed for
     *
     * @var string
     */
    protected $selectedFolder = 'inbox';

    /**
     * An user login all email related actions are performed for
     *
     * @var string
     */
    protected $selectedUser = null;

    /**
     * Constructor
     *
     * @param EwsConnector $connector
     */
    public function __construct(EwsConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get selected folder
     *
     * @return string
     */
    public function getSelectedFolder()
    {
        return $this->selectedFolder;
    }

    /**
     * Set selected folder
     *
     * @param $folder
     */
    public function selectFolder($folder)
    {
        $this->selectedFolder = $folder;
    }

    /**
     * Get email of selected user
     *
     * @return string
     */
    public function getSelectedUser()
    {
        return $this->selectedUser;
    }

    /**
     * Set email of selected user
     *
     * @param $email
     */
    public function selectUser($email)
    {
        $this->selectedUser = $email;
    }

    /**
     * Retrieve emails by the given criteria
     *
     * @param SearchQuery $query
     * @return Email[]
     */
    public function getEmails(SearchQuery $query = null)
    {
        $response = $this->connector->findItems(
            $this->getSelectedFolderId(),
            $this->getSelectedUserId(),
            $query,
            EwsType\ItemQueryTraversalType::SHALLOW,
            EwsType\DefaultShapeNamesType::DEFAULT_PROPERTIES
        );

        $result = array();
        foreach ($response as $item) {
            foreach ($item->RootFolder->Items->Message as $msg) {
                $email = new Email($this);
                $email
                    ->setId(new ItemId($msg->ItemId->Id, $msg->ItemId->ChangeKey))
                    ->setSubject($msg->Subject)
                    ->setFrom($msg->From->Mailbox->EmailAddress)
                    ->setSentAt($this->convertToDateTime($msg->DateTimeSent))
                    ->setReceivedAt($this->convertToDateTime($msg->DateTimeReceived))
                    ->setInternalDate($this->convertToDateTime($msg->DateTimeCreated))
                    ->setImportance($this->convertImportance($msg->Importance))
                    ->setMessageId($msg->InternetMessageId)
                    ->setXMessageId($msg->ItemId->Id)
                    ->setXThreadId($msg->ConversationId->Id);
                foreach ($msg->ToRecipients->Mailbox as $mailbox) {
                    $email->addToRecipient($mailbox->EmailAddress);
                }
                foreach ($msg->CcRecipients->Mailbox as $mailbox) {
                    $email->addCcRecipient($mailbox->EmailAddress);
                }
                foreach ($msg->BccRecipients->Mailbox as $mailbox) {
                    $email->addBccRecipient($mailbox->EmailAddress);
                }
                foreach ($msg->Attachments->FileAttachment as $attachment) {
                    $email->addAttachmentId($attachment->AttachmentId->Id);
                }

                $result[] = $email;
            }
        }

        return $result;
    }

    /**
     * Retrieve the body for the given email message
     *
     * @param ItemId $emailId
     * @return EmailBody
     */
    public function getEmailBody(ItemId $emailId)
    {
        $response = $this->connector->getItem(
            $this->convertToEwsItemId($emailId),
            EwsType\DefaultShapeNamesType::DEFAULT_PROPERTIES,
            EwsType\BodyTypeResponseType::BEST
        );

        $body = new EmailBody();
        $body
            ->setContent($response->Body->_)
            ->setBodyIsText($response->Body->BodyType === EwsType\BodyTypeType::TEXT);

        return $body;
    }

    /**
     * Retrieve email attachments
     *
     * @param string[] $attachmentIds
     * @return EmailAttachment[]
     */
    public function getEmailAttachments(array $attachmentIds)
    {
        $ids = array();
        foreach ($attachmentIds as $attachmentId) {
            $id = new EwsType\RequestAttachmentIdType();
            $id->Id = $attachmentId;
            $ids[] = $id;
        }

        $response = $this->connector->getAttachments(
            $ids,
            false,
            false,
            EwsType\BodyTypeResponseType::BEST
        );

        $result = array();
        foreach ($response as $item) {
            foreach ($item->Attachments->FileAttachment as $msg) {
                $attachment = new EmailAttachment($this);
                $attachment
                    ->setFileName($msg->Name)
                    ->setContentType($msg->ContentType)
                    ->setContent($msg->Content)
                    ->setContentTransferEncoding('BINARY');

                $result[] = $attachment;
            }
        }

        return $result;
    }

    /**
     * Get id of the selected folder
     *
     * @return EwsType\DistinguishedFolderIdType|EwsType\FolderIdType
     */
    protected function getSelectedFolderId()
    {
        $key = strtolower($this->selectedFolder);
        if (isset(static::$distinguishedFolderNames[$key])) {
            $result = new EwsType\DistinguishedFolderIdType();
            $result->Id = static::$distinguishedFolderNames[$key];

            return $result;
        }

        $result = new EwsType\FolderIdType();
        $result->Id = $this->selectedFolder;

        return $result;
    }

    /**
     * Get id of the selected user
     *
     * @return null|EwsType\ConnectingSIDType
     */
    protected function getSelectedUserId()
    {
        if (empty($this->selectedUser)) {
            return null;
        }

        $sid = new EwsType\ConnectingSIDType();
        $sid->PrimarySmtpAddress = $this->selectedUser;

        return $sid;
    }

    /**
     * Convert a string to DateTime
     *
     * @param string $value
     * @return \DateTime
     */
    protected function convertToDateTime($value)
    {
        $dt = new \DateTime($value);
        $dt->setTimezone(new \DateTimeZone('UTC'));

        return $dt;
    }

    /**
     * Convert email importance value from EwsType\ImportanceChoicesType to its integer representation
     *
     * @param string $val
     * @return integer
     */
    protected function convertImportance($val)
    {
        switch ($val) {
            case EwsType\ImportanceChoicesType::HIGH:
                return 1;
            case EwsType\ImportanceChoicesType::LOW:
                return -1;
            default:
                return 0;
        }
    }

    /**
     * Convert given ItemId object to EwsType\ItemIdType object
     *
     * @param ItemId $id
     * @return EwsType\ItemIdType
     */
    protected function convertToEwsItemId(ItemId $id)
    {
        $result = new EwsType\ItemIdType();
        $result->Id = $id->getId();
        $result->ChangeKey = $id->getChangeKey();

        return $result;
    }
}
