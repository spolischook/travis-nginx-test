<?php

namespace OroPro\Bundle\EwsBundle\Connector;

use OroPro\Bundle\EwsBundle\Connector\Search\QueryStringBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\RestrictionBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Ews\ExchangeWebServices;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;

/**
 * A base class for connectors intended to work with emails located
 * on Microsoft Exchange Server using Exchange Web Services (EWS).
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EwsConnector
{
    /**
     * @var ExchangeWebServices
     */
    protected $ews;

    /**
     * @param ExchangeWebServices $ews
     * @throws \InvalidArgumentException
     */
    public function __construct(ExchangeWebServices $ews)
    {
        if ($ews === null) {
            throw new \InvalidArgumentException('The EWS proxy must not be null.');
        }
        $this->ews = $ews;
    }

    /**
     * Gets the search query builder
     *
     * @return SearchQueryBuilder
     */
    public function getSearchQueryBuilder()
    {
        return new SearchQueryBuilder(
            new SearchQuery(
                new QueryStringBuilder(),
                new RestrictionBuilder()
            )
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * Finds items.
     *
     * @param EwsType\DistinguishedFolderIdType|EwsType\FolderIdType|EwsType\DistinguishedFolderIdNameType|EwsType\DistinguishedFolderIdType[]|EwsType\FolderIdType[]|EwsType\DistinguishedFolderIdNameType[]|array $parentFolder
     * @param SearchQuery|EwsType\RestrictionType|string $query The search query
     * @param \Closure $prepareRequest function (EwsType\FindItemType $request)
     * @return EwsType\FindItemResponseMessageType[]
     */
    // @codingStandardsIgnoreEnd
    public function findItems($parentFolder, $query = null, $prepareRequest = null)
    {
        $request = new EwsType\FindItemType();
        $request->ItemShape = new EwsType\ItemResponseShapeType();
        $request->ItemShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $request->Traversal = EwsType\ItemQueryTraversalType::SHALLOW;
        $request->ParentFolderIds = $this->createParentFolderIds($parentFolder);

        if ($query !== null) {
            $this->setSearchQuery($request, $query);
        }

        if ($prepareRequest) {
            $prepareRequest($request);
        }

        $response = $this->ews->FindItem($request);

        if ($response == null
            || !isset($response->ResponseMessages)
            || !isset($response->ResponseMessages->FindItemResponseMessage)
        ) {
            return array();
        }

        return $response->ResponseMessages->FindItemResponseMessage;
    }

    /**
     * Extracts email item ids from the given FindItemResponseMessage
     *
     * @param EwsType\FindItemResponseMessageType[] $findItemResponseMessage
     * @return EwsType\ItemIdType[]
     */
    public function extractItemIds(array $findItemResponseMessage)
    {
        return $this->extractIds($findItemResponseMessage, 'Message');
    }

    /**
     * Extracts contact ids from the given FindItemResponseMessage
     *
     * @param EwsType\FindItemResponseMessageType[] $findItemResponseMessage
     * @return EwsType\ItemIdType[]
     */
    public function extractContactIds(array $findItemResponseMessage)
    {
        return $this->extractIds($findItemResponseMessage, 'Contact');
    }

    /**
     * Extracts calendar item ids from the given FindItemResponseMessage
     *
     * @param EwsType\FindItemResponseMessageType[] $findItemResponseMessage
     * @return EwsType\ItemIdType[]
     */
    public function extractCalendarItemIds(array $findItemResponseMessage)
    {
        return $this->extractIds($findItemResponseMessage, 'CalendarItem');
    }

    /**
     * Extracts ids of the given item type from the given FindItemResponseMessage
     *
     * @param EwsType\FindItemResponseMessageType[] $findItemResponseMessage
     * @param string $itemTypeName The name of property where item's details are stored
     * @return EwsType\ItemIdType[]
     */
    protected function extractIds(array $findItemResponseMessage, $itemTypeName)
    {
        $result = array();
        foreach ($findItemResponseMessage as $respMsg) {
            if (isset($respMsg->RootFolder->Items->$itemTypeName)) {
                foreach ($respMsg->RootFolder->Items->$itemTypeName as $item) {
                    $result[] = $item->ItemId;
                }
            }
        }

        return $result;
    }

    // @codingStandardsIgnoreStart
    /**
     * Finds folders.
     *
     * @param EwsType\DistinguishedFolderIdType|EwsType\FolderIdType|EwsType\DistinguishedFolderIdNameType|EwsType\DistinguishedFolderIdType[]|EwsType\FolderIdType[]|EwsType\DistinguishedFolderIdNameType[]|array $parentFolder
     * @param \Closure $prepareRequest function (EwsType\FindFolderType $request)
     * @return EwsType\FindFolderResponseMessageType[]
     */
    // @codingStandardsIgnoreEnd
    public function findFolders($parentFolder, $prepareRequest = null)
    {
        $request = new EwsType\FindFolderType();
        $request->FolderShape = new EwsType\FolderResponseShapeType();
        $request->FolderShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $request->Traversal = EwsType\FolderQueryTraversalType::SHALLOW;
        $request->ParentFolderIds = $this->createParentFolderIds($parentFolder);

        if ($prepareRequest) {
            $prepareRequest($request);
        }

        $response = $this->ews->FindFolder($request);

        if ($response == null
            || !isset($response->ResponseMessages)
            || !isset($response->ResponseMessages->FindFolderResponseMessage)
        ) {
            return array();
        }

        return $response->ResponseMessages->FindFolderResponseMessage;
    }

    /**
     * Finds folders by its identifiers.
     *
     * @param EwsType\DistinguishedFolderIdNameType $folderName
     * @param \Closure $prepareRequest function (EwsType\GetFolderType $request)
     * @return EwsType\FolderInfoResponseMessageType[]
     */
    public function findDistinguishedFolder($folderName, $prepareRequest = null)
    {
        $request = new EwsType\GetFolderType();
        $request->FolderShape = new EwsType\FolderResponseShapeType();
        $request->FolderShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;

        $request->FolderIds = $this->createParentFolderIds($folderName);

        if ($prepareRequest) {
            $prepareRequest($request);
        }

        $response = $this->ews->GetFolder($request);

        if ($response == null
            || !isset($response->ResponseMessages)
            || !isset($response->ResponseMessages->GetFolderResponseMessage)
        ) {
            return array();
        }

        return $response->ResponseMessages->GetFolderResponseMessage;
    }

    /**
     * Extracts ids from the given FindFolderResponseMessage
     *
     * @param EwsType\FindFolderResponseMessageType[] $findFolderResponseMessage
     * @return EwsType\FolderIdType[]
     */
    public function extractFolderIds(array $findFolderResponseMessage)
    {
        $result = array();
        foreach ($findFolderResponseMessage as $respMsg) {
            if (isset($respMsg->RootFolder->Folders->Folder)) {
                foreach ($respMsg->RootFolder->Folders->Folder as $fld) {
                    $result[] = $fld->FolderId;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieves item detail by its id.
     *
     * @param EwsType\ItemIdType $id
     * @param \Closure $prepareRequest function (EwsType\GetItemType $request)
     * @return EwsType\MessageType
     * @throws \InvalidArgumentException
     */
    public function getItem(EwsType\ItemIdType $id, $prepareRequest = null)
    {
        if ($id === null) {
            throw new \InvalidArgumentException('The item identifier is not specified.');
        }
        $ids = array($id);
        $items = $this->getItems($ids, $prepareRequest);

        return empty($items) ? null : $items[0];
    }

    /**
     * Retrieves multiple items in a single call to Exchange Web Services (EWS).
     *
     * @param EwsType\ItemIdType[] $ids The list of ids of items
     * @param \Closure $prepareRequest function (EwsType\GetItemType $request)
     * @return EwsType\ItemInfoResponseMessageType[]
     * @throws \InvalidArgumentException
     */
    public function getItems(array $ids, $prepareRequest = null)
    {
        if (empty($ids)) {
            throw new \InvalidArgumentException('At least one item identifier must be specified.');
        }

        $request = new EwsType\GetItemType();

        $request->ItemShape = new EwsType\ItemResponseShapeType();
        $request->ItemShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $request->ItemShape->BodyType = EwsType\BodyTypeResponseType::BEST;

        $request->ItemIds = new EwsType\NonEmptyArrayOfBaseItemIdsType();
        $request->ItemIds->ItemId = $ids;

        if ($prepareRequest) {
            $prepareRequest($request);
        }

        $response = $this->ews->GetItem($request);

        if ($response == null
            || !isset($response->ResponseMessages)
            || !isset($response->ResponseMessages->GetItemResponseMessage)
        ) {
            return array();
        }

        return $response->ResponseMessages->GetItemResponseMessage;
    }

    /**
     * Extracts ids from ItemAttachment section of the given ItemInfoResponseMessage
     *
     * @param EwsType\ItemInfoResponseMessageType[] $itemInfoResponseMessage
     * @return EwsType\AttachmentIdType[]
     */
    public function extractItemAttachmentIds(array $itemInfoResponseMessage)
    {
        return $this->extractAttachmentIds($itemInfoResponseMessage, 'ItemAttachment');
    }

    /**
     * Extracts ids from FileAttachment section of the given ItemInfoResponseMessage
     *
     * @param EwsType\ItemInfoResponseMessageType[] $itemInfoResponseMessage
     * @return EwsType\AttachmentIdType[]
     */
    public function extractFileAttachmentIds(array $itemInfoResponseMessage)
    {
        return $this->extractAttachmentIds($itemInfoResponseMessage, 'FileAttachment');
    }

    /**
     * Extracts ids from the given section of the given ItemInfoResponseMessage
     *
     * @param EwsType\ItemInfoResponseMessageType[] $itemInfoResponseMessage
     * @param string $attachmentTypeName The name of property where attachment's details are stored
     * @return EwsType\AttachmentIdType[]
     */
    protected function extractAttachmentIds(array $itemInfoResponseMessage, $attachmentTypeName)
    {
        $result = array();
        foreach ($itemInfoResponseMessage as $respMsg) {
            if (isset($respMsg->Items->Message)) {
                foreach ($respMsg->Items->Message as $msg) {
                    if (isset($msg->Attachments->$attachmentTypeName)) {
                        foreach ($msg->Attachments->$attachmentTypeName as $att) {
                            $result[] = $att->AttachmentId;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param EwsType\RequestAttachmentIdType[] $ids The list of ids of attachments
     * @param bool $includeMimeContent Whether the MIME content of an attachment is returned
     * @param bool $filterHtmlContent Whether potentially unsafe HTML content is filtered from an attachment
     * @param EwsType\BodyTypeResponseType $bodyType Defines format of body to return
     * @return EwsType\AttachmentInfoResponseMessageType[]
     * @throws \InvalidArgumentException
     */
    public function getAttachments(
        array $ids,
        $includeMimeContent = false,
        $filterHtmlContent = false,
        $bodyType = EwsType\BodyTypeResponseType::BEST
    ) {
        if (empty($ids)) {
            throw new \InvalidArgumentException('At least one item identifier must be specified.');
        }

        $request = new EwsType\GetAttachmentType();

        $request->AttachmentShape = new EwsType\AttachmentResponseShapeType();
        $request->AttachmentShape->IncludeMimeContent = $includeMimeContent;
        $request->AttachmentShape->FilterHtmlContent = $filterHtmlContent;
        $request->AttachmentShape->BodyType = $bodyType;

        $request->AttachmentIds = new EwsType\NonEmptyArrayOfRequestAttachmentIdsType();
        $request->AttachmentIds->AttachmentId = $ids;

        $response = $this->ews->GetAttachment($request);

        if ($response == null
            || !isset($response->ResponseMessages)
            || !isset($response->ResponseMessages->GetAttachmentResponseMessage)
        ) {
            return array();
        }

        return $response->ResponseMessages->GetAttachmentResponseMessage;
    }

    /**
     * Get a user, whose mailbox is currently used
     *
     * @return EwsType\ConnectingSIDType|null
     */
    public function getTargetUser()
    {
        $impersonation = $this->ews->getImpersonation();

        return $impersonation
            ? $impersonation->ConnectingSID
            : null;
    }

    /**
     * Set a user, whose mailbox you want to use
     *
     * @param EwsType\ConnectingSIDType|null $targetUser
     */
    public function setTargetUser($targetUser)
    {
        if ($targetUser === null) {
            $this->ews->setImpersonation(null);
        } else {
            $ei = new EwsType\ExchangeImpersonationType();
            $ei->ConnectingSID = $targetUser;
            $this->ews->setImpersonation($ei);
        }
    }

    /**
     * Sets appropriate properties of given FindItemType object based on a value of the query parameter
     *
     * @param EwsType\FindItemType $request The FindItem request for which the search criterion to be set
     * @param SearchQuery|EwsType\RestrictionType|string $query The search query
     * @throws \InvalidArgumentException
     */
    protected function setSearchQuery(EwsType\FindItemType $request, $query)
    {
        if ($query instanceof SearchQuery) {
            if (!$query->isEmpty()) {
                switch ($query->getQueryType()) {
                    case SearchQuery::QUERY_STRING:
                        if (!$this->ews->isQueryStringSupported()) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    'The query string search is not supported by %s.',
                                    $this->ews->getVersion()
                                )
                            );
                        }
                        $request->QueryString = $query->convertToQueryString();
                        break;
                    case SearchQuery::RESTRICTION:
                        $request->Restriction = $query->convertToRestriction();
                        break;
                    default:
                        // SearchQuery::AUTO
                        if ($this->ews->isQueryStringSupported()) {
                            $request->QueryString = $query->convertToQueryString();
                        } else {
                            $request->Restriction = $query->convertToRestriction();
                        }
                        break;
                }
            }
        } elseif ($query instanceof EwsType\RestrictionType) {
            $request->Restriction = $query;
        } elseif (is_string($query)) {
            if (strlen($query) > 0) {
                $request->QueryString = $query;
            }
        } else {
            throw new \InvalidArgumentException('Invalid query type.');
        }
    }

    // @codingStandardsIgnoreStart
    /**
     * Creates NonEmptyArrayOfBaseFolderIdsType object based on a value of the parentFolder parameter
     *
     * @param EwsType\DistinguishedFolderIdType|EwsType\FolderIdType|EwsType\DistinguishedFolderIdNameType|EwsType\DistinguishedFolderIdType[]|EwsType\FolderIdType[]|EwsType\DistinguishedFolderIdNameType[]|array $parentFolder
     * @return EwsType\NonEmptyArrayOfBaseFolderIdsType
     */
    // @codingStandardsIgnoreEnd
    protected function createParentFolderIds($parentFolder)
    {
        $parentFolderIds = new EwsType\NonEmptyArrayOfBaseFolderIdsType();
        if (is_array($parentFolder)) {
            foreach ($parentFolder as $fld) {
                $this->setParentFolderId($parentFolderIds, $fld);
            }
        } else {
            $this->setParentFolderId($parentFolderIds, $parentFolder);
        }

        return $parentFolderIds;
    }

    /**
     * Sets appropriate properties of given NonEmptyArrayOfBaseFolderIdsType object
     * based on a value of the folderId parameter
     *
     * @param EwsType\NonEmptyArrayOfBaseFolderIdsType $parentFolderIds
     * @param EwsType\DistinguishedFolderIdType|EwsType\FolderIdType|EwsType\DistinguishedFolderIdNameType $folderId
     */
    protected function setParentFolderId(EwsType\NonEmptyArrayOfBaseFolderIdsType $parentFolderIds, $folderId)
    {
        if ($folderId instanceof EwsType\FolderIdType) {
            if (!isset($parentFolderIds->FolderId)) {
                $parentFolderIds->FolderId = array();
            }
            $parentFolderIds->FolderId[] = $folderId;
        } else {
            if (!isset($parentFolderIds->FolderId)) {
                $parentFolderIds->DistinguishedFolderId = array();
            }
            if ($folderId instanceof EwsType\DistinguishedFolderIdType) {
                $parentFolderIds->DistinguishedFolderId[] = $folderId;
            } else {
                $distinguishedFolderId = new EwsType\DistinguishedFolderIdType();
                $distinguishedFolderId->Id = $folderId;
                $parentFolderIds->DistinguishedFolderId[] = $distinguishedFolderId;
            }
        }
    }

    /**
     * @param string $login
     *
     * @return EwsType\GetPasswordExpirationDateResponseMessageType
     */
    public function getPasswordExpirationDate($login)
    {
        $request = new EwsType\GetPasswordExpirationDateType();
        $request->MailboxSmtpAddress = $login;

        return $this->ews->GetPasswordExpirationDate($request);
    }

    /**
     * @return bool
     */
    public function isExchange2007()
    {
        return $this->ews->isExchange2007();
    }
}
