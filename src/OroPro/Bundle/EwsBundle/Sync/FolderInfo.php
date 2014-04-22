<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;

class FolderInfo
{
    /**
     * @param EwsEmailFolder $ewsFolder
     * @param bool           $needSynchronization
     */
    public function __construct(EwsEmailFolder $ewsFolder, $needSynchronization)
    {
        $this->ewsFolder           = $ewsFolder;
        $this->needSynchronization = $needSynchronization;
    }

    /**
     * @var EwsEmailFolder
     */
    public $ewsFolder;

    /**
     * @var bool
     */
    public $needSynchronization = false;

    /**
     * @var string
     */
    public $folderType;
}
