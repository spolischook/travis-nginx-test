<?php

namespace OroPro\Bundle\EwsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;

/**
 * EWS Email Folder
 *
 * @ORM\Table(name="oro_email_folder_ews")
 * @ORM\Entity(repositoryClass="OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailFolderRepository")
 */
class EwsEmailFolder
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var EmailFolder
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\EmailFolder")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", nullable=false)
     */
    protected $folder;

    /**
     * @var string
     *
     * @ORM\Column(name="ews_id", type="string", length=255)
     */
    protected $ewsId;

    /**
     * @var string
     *
     * @ORM\Column(name="ews_change_key", type="string", length=255)
     */
    protected $ewsChangeKey;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get related email object
     *
     * @return EmailFolder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set related email object
     *
     * @param EmailFolder $folder
     * @return EwsEmailFolder
     */
    public function setFolder(EmailFolder $folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get email EWS folder id
     *
     * @return string
     */
    public function getEwsId()
    {
        return $this->ewsId;
    }

    /**
     * Set email EWS folder id
     *
     * @param string $ewsId
     * @return EwsEmailFolder
     */
    public function setEwsId($ewsId)
    {
        $this->ewsId = $ewsId;

        return $this;
    }

    /**
     * Get email EWS folder change key
     *
     * @return string
     */
    public function getEwsChangeKey()
    {
        return $this->ewsChangeKey;
    }

    /**
     * Set email EWS folder change key
     *
     * @param string $ewsChangeKey
     * @return EwsEmailFolder
     */
    public function setEwsChangeKey($ewsChangeKey)
    {
        $this->ewsChangeKey = $ewsChangeKey;

        return $this;
    }
}
