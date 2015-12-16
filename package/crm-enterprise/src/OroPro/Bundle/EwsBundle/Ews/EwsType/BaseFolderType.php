<?php

namespace OroPro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * BaseFolderType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class BaseFolderType
{
    /**
     * @var FolderIdType
     * @access public
     */
    public $FolderId;

    /**
     * @var FolderIdType
     * @access public
     */
    public $ParentFolderId;

    /**
     * @var string
     * @access public
     */
    public $FolderClass;

    /**
     * @var string
     * @access public
     */
    public $DisplayName;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $TotalCount;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $ChildFolderCount;

    /**
     * @var ExtendedPropertyType[]
     * @access public
     */
    public $ExtendedProperty;

    /**
     * @var ManagedFolderInformationType
     * @access public
     */
    public $ManagedFolderInformation;

    /**
     * @var EffectiveRightsType
     * @access public
     */
    public $EffectiveRights;
}
// @codingStandardsIgnoreEnd
