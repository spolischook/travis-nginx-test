<?php

namespace OroPro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * CreateFolderType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class CreateFolderType extends BaseRequestType
{
    /**
     * @var TargetFolderIdType
     * @access public
     */
    public $ParentFolderId;

    /**
     * @var NonEmptyArrayOfFoldersType
     * @access public
     */
    public $Folders;
}
// @codingStandardsIgnoreEnd
