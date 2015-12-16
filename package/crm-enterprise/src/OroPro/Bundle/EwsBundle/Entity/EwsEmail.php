<?php

namespace OroPro\Bundle\EwsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * EWS Email
 *
 * @ORM\Table(
 *      name="oro_email_ews",
 *      indexes={@ORM\Index(name="idx_oro_email_ews", columns={"ews_id"})}
 * )
 * @ORM\Entity(repositoryClass="OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailRepository")
 */
class EwsEmail
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
     * @var Email
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\Email")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false)
     */
    protected $email;

    /**
     * @var EwsEmailFolder
     *
     * @ORM\ManyToOne(targetEntity="OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder")
     * @ORM\JoinColumn(name="ews_folder_id", referencedColumnName="id", nullable=false)
     */
    protected $ewsFolder;

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
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set related email object
     *
     * @param Email $email
     *
     * @return EwsEmail
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param EwsEmailFolder $ewsFolder
     *
     * @return $this
     */
    public function setEwsFolder($ewsFolder)
    {
        $this->ewsFolder = $ewsFolder;

        return $this;
    }

    /**
     * @return EwsEmailFolder
     */
    public function getEwsFolder()
    {
        return $this->ewsFolder;
    }

    /**
     * Get email EWS item id
     *
     * @return string
     */
    public function getEwsId()
    {
        return $this->ewsId;
    }

    /**
     * Set email EWS item id
     *
     * @param string $ewsId
     *
     * @return EwsEmail
     */
    public function setEwsId($ewsId)
    {
        $this->ewsId = $ewsId;

        return $this;
    }

    /**
     * Get email EWS item change key
     *
     * @return string
     */
    public function getEwsChangeKey()
    {
        return $this->ewsChangeKey;
    }

    /**
     * Set email EWS item change key
     *
     * @param string $ewsChangeKey
     *
     * @return EwsEmail
     */
    public function setEwsChangeKey($ewsChangeKey)
    {
        $this->ewsChangeKey = $ewsChangeKey;

        return $this;
    }
}
