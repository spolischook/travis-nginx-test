<?php

namespace OroPro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * BaseObjectChangedEventType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class BaseObjectChangedEventType extends BaseNotificationEventType
{
    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $TimeStamp;

    /**
     * @var FolderIdType
     * @access public
     */
    public $FolderId;

    /**
     * @var ItemIdType
     * @access public
     */
    public $ItemId;

    /**
     * @var FolderIdType
     * @access public
     */
    public $ParentFolderId;
}
// @codingStandardsIgnoreEnd
