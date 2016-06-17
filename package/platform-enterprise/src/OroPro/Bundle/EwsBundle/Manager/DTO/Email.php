<?php

namespace OroPro\Bundle\EwsBundle\Manager\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHeader;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class Email extends EmailHeader
{
    /**
     * @var EwsEmailManager
     */
    protected $manager;

    /**
     * @var ItemId
     */
    protected $id;

    /**
     * @var EmailBody
     */
    protected $body = null;

    /**
     * @var string[]
     */
    protected $attachmentIds = array();

    /**
     * @var EmailAttachment[]
     */
    protected $attachments;

    /**
     * @var bool
     */
    protected $seen;

    /**
     * Constructor
     *
     * @param EwsEmailManager $manager
     */
    public function __construct(EwsEmailManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get item id
     *
     * @return ItemId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set item id
     *
     * @param ItemId $id
     *
     * @return self
     */
    public function setId(ItemId $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get email body
     *
     * @return EmailBody
     */
    public function getBody()
    {
        if ($this->body === null) {
            $this->body = $this->manager->getEmailBody($this->id);
        }

        return $this->body;
    }

    /**
     * Get email body
     *
     * @param EmailBody $body
     *
     * @return self
     */
    public function setBody(EmailBody $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get ids of email attachments
     *
     * @return string[]
     */
    public function getAttachmentIds()
    {
        return $this->attachmentIds;
    }

    /**
     * Add id of email attachment
     *
     * @param string $id
     *
     * @return self
     */
    public function addAttachmentId($id)
    {
        $this->attachmentIds[] = $id;

        return $this;
    }

    /**
     * Get email attachments
     *
     * @return EmailAttachment[]
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $this->attachments = !empty($this->attachmentIds)
                ? $this->manager->getEmailAttachments($this->attachmentIds)
                : array();
        }

        return $this->attachments;
    }

    /**
     * Set seen status
     *
     * @param bool $seen
     *
     * @return self
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

        return $this;
    }

    /**
     * Get seen status
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->seen;
    }
}
