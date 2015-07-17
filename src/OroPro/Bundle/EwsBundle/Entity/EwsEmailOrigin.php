<?php

namespace OroPro\Bundle\EwsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * EWS Email Origin
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class EwsEmailOrigin extends EmailOrigin
{
    /**
     * @var string
     *
     * @ORM\Column(name="ews_server", type="string", length=255, nullable=true)
     */
    protected $server;

    /**
     * @var string
     *
     * @ORM\Column(name="ews_user_email", type="string", length=255, nullable=true)
     */
    protected $userEmail;

    /**
     * Gets the EWS server name
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Sets the EWS server name
     *
     * @param string $server
     * @return EwsEmailOrigin
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Gets the user's login email
     *
     * @return string
     */
    public function getUserEmail()
    {
        return $this->userEmail;
    }

    /**
     * Sets the user's login email
     *
     * @param string $userEmail
     * @return EwsEmailOrigin
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s (%s)', $this->userEmail, $this->server);
    }

    /**
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        if ($this->mailboxName === null) {
            $this->mailboxName = $this->userEmail;
        }
    }
}
