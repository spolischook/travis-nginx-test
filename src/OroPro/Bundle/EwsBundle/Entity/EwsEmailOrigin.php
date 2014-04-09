<?php

namespace OroPro\Bundle\EwsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * EWS Email Origin
 *
 * @ORM\Entity
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
     * @ORM\Column(name="ews_user", type="string", length=255, nullable=true)
     */
    protected $user;

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
     * Gets the user name
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the user name
     *
     * @param string $user
     * @return EwsEmailOrigin
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
