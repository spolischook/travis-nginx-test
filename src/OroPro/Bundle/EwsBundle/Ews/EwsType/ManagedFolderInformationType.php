<?php

namespace OroPro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * Compound property for Managed Folder related information for Managed Folders.
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class ManagedFolderInformationType
{
    /**
     * @var boolean
     * @access public
     */
    public $CanDelete;

    /**
     * @var boolean
     * @access public
     */
    public $CanRenameOrMove;

    /**
     * @var boolean
     * @access public
     */
    public $MustDisplayComment;

    /**
     * @var boolean
     * @access public
     */
    public $HasQuota;

    /**
     * @var boolean
     * @access public
     */
    public $IsManagedFoldersRoot;

    /**
     * @var string
     * @access public
     */
    public $ManagedFolderId;

    /**
     * @var string
     * @access public
     */
    public $Comment;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $StorageQuota;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $FolderSize;

    /**
     * @var string
     * @access public
     */
    public $HomePage;
}
// @codingStandardsIgnoreEnd
