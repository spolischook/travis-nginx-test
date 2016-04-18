<?php

namespace OroPro\Bundle\EwsBundle\Manager;

use Oro\Bundle\EmailBundle\Model\FolderType;

use OroPro\Bundle\EwsBundle\Connector\EwsAdditionalPropertiesBuilder;
use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Ews\EwsType\ArrayOfRecipientsType;
use OroPro\Bundle\EwsBundle\Manager\DTO\EmailAttachment;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\DTO\EmailBody;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EwsEmailManager
{
    /**
     * The list of special folder names, also known as EWS distinguished folders
     *
     * @var array
     */
    protected static $distinguishedFolderNames = [
        FolderType::INBOX  => EwsType\DistinguishedFolderIdNameType::INBOX,
        FolderType::SENT   => EwsType\DistinguishedFolderIdNameType::SENTITEMS,
        FolderType::DRAFTS => EwsType\DistinguishedFolderIdNameType::DRAFTS,
        FolderType::TRASH  => EwsType\DistinguishedFolderIdNameType::DELETEDITEMS,
    ];

    /**
     * @var EwsConnector
     */
    protected $connector;

    /**
     * @var boolean
     */
    protected $attachmentSyncEnabled;

    /**
     * @var int
     */
    protected $attachmentMaxSize;

    /**
     * A mailbox name all email related actions are performed for
     *
     * @var EwsType\DistinguishedFolderIdType|EwsType\FolderIdType
     */
    protected $selectedFolder;

    /**
     * Constructor
     *
     * @param EwsConnector $connector
     */
    public function __construct(EwsConnector $connector)
    {
        $this->connector = $connector;
        $this->attachmentSyncEnabled = true;
        $this->attachmentMaxSize = 0;

        $this->selectFolder(FolderType::INBOX);
    }

    /**
     * Set enable/disable attachment sync
     *
     * @param bool $enabled
     */
    public function setAttachmentSyncEnabled($enabled)
    {
        $this->attachmentSyncEnabled = $enabled;
    }

    /**
     * Maximum allowed attachment size. 0 - unlimited
     *
     * @param int $size
     */
    public function setAttachmentMaxSize($size)
    {
        $this->attachmentMaxSize = $size;
    }

    /**
     * Get selected folder
     *
     * @return EwsType\DistinguishedFolderIdType|EwsType\FolderIdType
     */
    public function getSelectedFolder()
    {
        return $this->selectedFolder;
    }

    /**
     * Set selected folder
     *
     * @param string $folder
     */
    public function selectFolder($folder)
    {
        $this->selectedFolder = is_string($folder)
            ? $this->getFolderId($folder)
            : $folder;
    }

    /**
     * Get the user all email related actions are performed for
     *
     * @return EwsType\ConnectingSIDType|null
     */
    public function getSelectedUser()
    {
        return $this->connector->getTargetUser();
    }

    /**
     * Set the user all email related actions are performed for
     *
     * @param string $userEmail
     */
    public function selectUser($userEmail)
    {
        $this->connector->setTargetUser($this->getUserId($userEmail));
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
     * @return EwsType\FolderType[] key = FolderId->Id
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

        $result = [];
        foreach ($response as $item) {
            if ($item->RootFolder->Folders->Folder) {
                foreach ($item->RootFolder->Folders->Folder as $folder) {
                    $result[$folder->FolderId->Id] = $folder;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve emails by the given criteria
     *
     * @param SearchQuery $query
     * @param \Closure    $prepareRequest
     *
     * @return Email[]
     */
    public function getEmails(SearchQuery $query = null, $prepareRequest = null)
    {
        $response = $this->connector->findItems(
            $this->selectedFolder,
            $query,
            $prepareRequest
        );

        $ids = [];
        foreach ($response as $item) {
            if ($item->RootFolder->Items->Message) {
                foreach ($item->RootFolder->Items->Message as $msg) {
                    $ids[] = $msg->ItemId;
                }
            }
        }

        $result = [];
        if (!empty($ids)) {
            $response = $this->connector->getItems(
                $ids,
                function (EwsType\GetItemType $request) {
                    $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();
                    $fieldUris = [
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
                    ];

                    if (!$this->connector->isExchange2007()) {
                        $fieldUris[] = EwsType\UnindexedFieldURIType::ITEM_CONVERSATION_ID;
                    }

                    $additionalPropertiesBuilder->addUnindexedFieldUris($fieldUris);
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
     * @param ItemId   $emailId
     * @param callable $prepareRequest
     *
     * @return null|Email
     */
    public function findEmail(ItemId $emailId, \Closure $prepareRequest = null)
    {
        /** @var EwsType\ItemIdType $ewsItemId */
        $ewsItemId = $this->convertToEwsItemId($emailId);

        /** @var EwsType\ItemInfoResponseMessageType $msg */
        $msg = $this->connector->getItem(
            $ewsItemId,
            function (EwsType\GetItemType $request) {
                $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();

                $fieldUris = [
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
                    EwsType\UnindexedFieldURIType::ITEM_BODY,
                    EwsType\UnindexedFieldURIType::ITEM_HAS_ATTACHMENTS,
                    EwsType\UnindexedFieldURIType::ITEM_ATTACHMENTS,
                ];

                if (!$this->connector->isExchange2007()) {
                    $fieldUris[] = EwsType\UnindexedFieldURIType::ITEM_CONVERSATION_ID;
                }

                $additionalPropertiesBuilder->addUnindexedFieldUris($fieldUris);

                $request->ItemShape->AdditionalProperties = $additionalPropertiesBuilder->get();
            }
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
            function (EwsType\GetItemType $request) {
                $additionalPropertiesBuilder = new EwsAdditionalPropertiesBuilder();
                $additionalPropertiesBuilder->addUnindexedFieldUri(EwsType\UnindexedFieldURIType::ITEM_BODY);
                $request->ItemShape->AdditionalProperties = $additionalPropertiesBuilder->get();
            }
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
        $ids = [];
        foreach ($attachmentIds as $attachmentId) {
            $id = new EwsType\RequestAttachmentIdType();
            $id->Id = $attachmentId;
            $ids[] = $id;
        }

        $response = $this->connector->getAttachments($ids);

        $result = [];
        foreach ($response as $item) {
            if ($item->Attachments->FileAttachment) {
                foreach ($item->Attachments->FileAttachment as $msg) {
                    $attachment = new EmailAttachment();
                    $attachment
                        ->setFileName($msg->Name)
                        ->setFileSize(strlen($msg->Content))
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
     * Get the name of distinguished folder by its type
     *
     * @param string $folderType
     * @return string|null
     */
    public function getDistinguishedFolderName($folderType)
    {
        $key = strtolower($folderType);
        if (!isset(static::$distinguishedFolderNames[$key])) {
            return null;
        }

        return static::$distinguishedFolderNames[$key];
    }

    /**
     * Get EWS id of the given folder
     *
     * @param string $folder The folder type or id if folder type is 'other'
     * @return EwsType\DistinguishedFolderIdType|EwsType\FolderIdType
     */
    public function getFolderId($folder)
    {
        $distinguishedFolderName = $this->getDistinguishedFolderName($folder);
        if ($distinguishedFolderName) {
            $result = new EwsType\DistinguishedFolderIdType();
            $result->Id = $distinguishedFolderName;
        } else {
            $result = new EwsType\FolderIdType();
            $result->Id = $folder;
        }

        return $result;
    }

    /**
     * Get id of the selected user
     *
     * @param string|null $userEmail
     * @return EwsType\ConnectingSIDType|null
     */
    protected function getUserId($userEmail)
    {
        if (empty($userEmail)) {
            return null;
        }

        $sid = new EwsType\ConnectingSIDType();
        $sid->PrimarySmtpAddress = $userEmail;

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
     */
    protected function convertToEmail(EwsType\MessageType $msg)
    {
        $email = new Email($this);
        $email
            ->setId(new ItemId($msg->ItemId->Id, $msg->ItemId->ChangeKey))
            ->setSeen($msg->IsRead)
            ->setSubject(empty($msg->Subject) ? '' : $msg->Subject)
            ->setFrom($msg->From->Mailbox->EmailAddress)
            ->setSentAt($this->convertToDateTime($msg->DateTimeSent))
            ->setReceivedAt($this->convertToDateTime($msg->DateTimeReceived))
            ->setInternalDate($this->convertToDateTime($msg->DateTimeCreated))
            ->setImportance($this->convertImportance($msg->Importance))
            ->setMessageId($msg->InternetMessageId)
            ->setRefs($msg->References)
            ->setXMessageId($msg->ItemId->Id)
            ->setXThreadId($msg->ConversationId != null ? $msg->ConversationId->Id : null);

        $this->copyRecipients($msg, $email);
        $this->copyEmailBody($msg, $email);
        $this->copyAttachments($msg, $email);

        return $email;
    }

    /**
     * @param EwsType\MessageType $msg
     * @param Email $email
     */
    protected function copyRecipients(EwsType\MessageType $msg, Email $email)
    {
        if ($msg->ToRecipients instanceof ArrayOfRecipientsType) {
            foreach ($msg->ToRecipients->Mailbox as $mailbox) {
                $email->addToRecipient($mailbox->EmailAddress);
            }
        }

        if ($msg->CcRecipients instanceof ArrayOfRecipientsType) {
            foreach ($msg->CcRecipients->Mailbox as $mailbox) {
                $email->addCcRecipient($mailbox->EmailAddress);
            }
        }

        if ($msg->BccRecipients instanceof ArrayOfRecipientsType) {
            foreach ($msg->BccRecipients->Mailbox as $mailbox) {
                $email->addBccRecipient($mailbox->EmailAddress);
            }
        }
    }

    /**
     * @param EwsType\MessageType $msg
     * @param Email $email
     */
    protected function copyEmailBody(EwsType\MessageType $msg, Email $email)
    {
        if (null != $msg->Body) {
            $body = new EmailBody();
            $body
                ->setContent($msg->Body->_)
                ->setBodyIsText($msg->Body->BodyType === EwsType\BodyTypeType::TEXT);
            $email->setBody($body);
        }
    }

    /**
     * @param EwsType\MessageType $msg
     * @param Email $email
     */
    protected function copyAttachments(EwsType\MessageType $msg, Email $email)
    {
        if ($this->attachmentSyncEnabled && null !== $msg->Attachments) {
            foreach ($msg->Attachments->FileAttachment as $attachment) {
                if ($this->attachmentMaxSize === 0 || $attachment->Size / 1024 / 1024 <= $this->attachmentMaxSize) {
                    $email->addAttachmentId($attachment->AttachmentId->Id);
                }
            }
        }
    }
}
