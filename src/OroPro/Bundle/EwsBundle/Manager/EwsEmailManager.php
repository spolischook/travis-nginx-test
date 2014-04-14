<?php

namespace OroPro\Bundle\EwsBundle\Manager;

use OroPro\Bundle\EwsBundle\Connector\EwsAdditionalPropertiesBuilder;
use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Ews\EwsException;
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
        $this->connector->setTargetUser($this->getUserId($email));
    }

    /**
     * Gets the search query builder
     *
     * @return SearchQueryBuilder
     */
    public function getSearchQueryBuilder()
    {
        return $this->connector->getSearchQueryBuilder();
    }

    /**
     * Retrieve the distinguished folder by its name
     *
     * @param EwsType\DistinguishedFolderIdNameType|string $folderName
     * @return EwsType\FolderType
     */
    public function getDistinguishedFolder($folderName)
    {
        $response = $this->connector->findDistinguishedFolder(
            $folderName,
            function (EwsType\GetFolderType $request) {
                $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();
                $additionalPropertiesBuilder->addUnindexedFieldUri(EwsType\UnindexedFieldURIType::FOLDER_DISPLAY_NAME);
                $request->FolderShape->AdditionalProperties = $additionalPropertiesBuilder->get();
            }
        );

        $result = null;
        foreach ($response as $item) {
            if ($item->Folders->Folder) {
                foreach ($item->Folders->Folder as $folder) {
                    $result = $folder;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve folders
     *
     * @param string|null $parentFolder The global name of a parent folder.
     * @param bool $recursive True to get all subordinate folders
     * @return array
     */
    public function getFolders($parentFolder = null, $recursive = false)
    {
        if ($parentFolder === null) {
            $parentFolder = EwsType\DistinguishedFolderIdNameType::MSGFOLDERROOT;
        }

        $response = $this->connector->findFolders(
            $parentFolder,
            function (EwsType\FindFolderType $request) use ($recursive) {
                $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();
                $additionalPropertiesBuilder->addUnindexedFieldUri(EwsType\UnindexedFieldURIType::FOLDER_DISPLAY_NAME);
                if ($recursive) {
                    $request->Traversal = EwsType\FolderQueryTraversalType::DEEP;
                    $additionalPropertiesBuilder->addUnindexedFieldUri(
                        EwsType\UnindexedFieldURIType::FOLDER_PARENT_FOLDER_ID
                    );
                }
                $request->FolderShape->AdditionalProperties = $additionalPropertiesBuilder->get();
            }
        );

        $result = array();
        foreach ($response as $item) {
            if ($item->RootFolder->Folders->Folder) {
                foreach ($item->RootFolder->Folders->Folder as $folder) {
                    $result[] = $folder;
                }
            }
        }

        return $result;
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
            $query
        );

        $ids = array();
        foreach ($response as $item) {
            if ($item->RootFolder->Items->Message) {
                foreach ($item->RootFolder->Items->Message as $msg) {
                    $ids[] = $msg->ItemId;
                }
            }
        }

        $result = array();
        if (!empty($ids)) {
            $response = $this->connector->getItems(
                $ids,
                function (EwsType\GetItemType $request) {
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
                    $request->ItemShape->AdditionalProperties = $additionalPropertiesBuilder->get();
                }
            );
            foreach ($response as $item) {
                if ($item->Items->Message) {
                    foreach ($item->Items->Message as $msg) {
                        $result[] = $this->convertToEmail($msg);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve email by its Id
     *
     * @param ItemId $emailId
     * @throws \RuntimeException
     * @return null|Email
     */
    public function findEmail(ItemId $emailId)
    {
        /** @var EwsType\ItemIdType $ewsItemId */
        $ewsItemId = $this->convertToEwsItemId($emailId);

        /** @var EwsType\ItemInfoResponseMessageType $msg */
        $msg = $this->connector->getItem(
            $ewsItemId,
            EwsType\DefaultShapeNamesType::DEFAULT_PROPERTIES,
            EwsType\BodyTypeResponseType::BEST
        );

        return $this->convertToEmail($msg->Items->Message[0]);
    }

    /**
     * Retrieve the body for the given email message
     *
     * @param ItemId $emailId
     * @return EmailBody
     */
    public function getEmailBody(ItemId $emailId)
    {
        /** @var EwsType\ItemInfoResponseMessageType $response */
        $response = $this->connector->getItem(
            $this->convertToEwsItemId($emailId),
            EwsType\DefaultShapeNamesType::DEFAULT_PROPERTIES,
            EwsType\BodyTypeResponseType::BEST
        );

        $messageBody = $response->Items->Message[0]->Body;

        $body = new EmailBody();
        $body
            ->setContent($messageBody->_)
            ->setBodyIsText($messageBody->BodyType === EwsType\BodyTypeType::TEXT);

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
            if ($item->Attachments->FileAttachment) {
                foreach ($item->Attachments->FileAttachment as $msg) {
                    $attachment = new EmailAttachment();
                    $attachment
                        ->setFileName($msg->Name)
                        ->setContentType($msg->ContentType)
                        ->setContent($msg->Content)
                        ->setContentTransferEncoding('BINARY');

                    $result[] = $attachment;
                }
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
     * @param string|null $email
     * @return null|EwsType\ConnectingSIDType
     */
    protected function getUserId($email)
    {
        if (empty($email)) {
            return null;
        }

        $sid = new EwsType\ConnectingSIDType();
        $sid->PrimarySmtpAddress = $email;

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

    /**
     * Creates Email DTO for the given email message
     *
     * @param EwsType\MessageType $msg
     * @return Email
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function convertToEmail(EwsType\MessageType $msg)
    {
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
            ->setXThreadId($msg->ConversationId != null ? $msg->ConversationId->Id : null);

        foreach ($msg->ToRecipients->Mailbox as $mailbox) {
            $email->addToRecipient($mailbox->EmailAddress);
        }

        if (null != $msg->CcRecipients) {
            foreach ($msg->CcRecipients->Mailbox as $mailbox) {
                $email->addCcRecipient($mailbox->EmailAddress);
            }
        }

        if (null != $msg->BccRecipients) {
            foreach ($msg->BccRecipients->Mailbox as $mailbox) {
                $email->addBccRecipient($mailbox->EmailAddress);
            }
        }

        if (null != $msg->Attachments) {
            foreach ($msg->Attachments->FileAttachment as $attachment) {
                $email->addAttachmentId($attachment->AttachmentId->Id);
            }
        }

        return $email;
    }
}
