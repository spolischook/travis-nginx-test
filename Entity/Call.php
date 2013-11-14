<?php

namespace OroCRM\Bundle\CallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;
use OroCRM\Bundle\CallBundle\Entity\CallStatus;

/**
 * Call
 *
 * @ORM\Table(name="orocrm_call")
 * @ORM\Entity
 */

class Call
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
     * @var User
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Contact
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\ContactBundle\Entity\Contact")
     * @ORM\JoinColumn(name="related_contact_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $relatedContact;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="related_account_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $relatedAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true)
     */
    protected $phoneNumber;

    /**
     * @var ContactPhone
     *
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\ContactBundle\Entity\ContactPhone")
     * @ORM\JoinColumn(name="contact_phone_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $contactPhoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="callDateTime", type="datetime")
     */
    protected $callDateTime;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="OroCRM\Bundle\CallBundle\Entity\CallStatus")
     * @ORM\JoinColumn(name="call_status_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $callStatus;

    /**
     * @var \Time
     *
     * @ORM\Column(name="duration", type="time", nullable=true)
     */
    protected $duration;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isOutgoing", type="boolean")
     */
    protected $isOutgoing;


    public function __construct()
    {
        $this->isOutgoing = true;
        $this->callDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
    }

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
     * Set subject
     *
     * @param string $subject
     * @return Call
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set phoneNumber
     *
     * @param string $phoneNumber
     * @return Call
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    
        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return string 
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return Call
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    
        return $this;
    }

    /**
     * Get notes
     *
     * @return string 
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set callDateTime
     *
     * @param \DateTime $callDateTime
     * @return Call
     */
    public function setCallDateTime($callDateTime)
    {
        $this->callDateTime = $callDateTime;
    
        return $this;
    }

    /**
     * Get callDateTime
     *
     * @return \DateTime 
     */
    public function getCallDateTime()
    {
        return $this->callDateTime;
    }

    /**
     * Set duration
     *
     * @param \Time $duration
     * @return Call
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    
        return $this;
    }

    /**
     * Get duration
     *
     * @return \Time 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set isOutgoing
     *
     * @param boolean $isOutgoing
     * @return Call
     */
    public function setIsOutgoing($isOutgoing)
    {
        $this->isOutgoing = $isOutgoing;
    
        return $this;
    }

    /**
     * Get isOutgoing
     *
     * @return boolean 
     */
    public function getIsOutgoing()
    {
        return $this->isOutgoing;
    }

    /**
     * Set owner
     *
     * @param User $owner
     * @return Call
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    
        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set relatedContact
     *
     * @param Contact $relatedContact
     * @return Call
     */
    public function setRelatedContact(Contact $relatedContact = null)
    {
        $this->relatedContact = $relatedContact;
    
        return $this;
    }

    /**
     * Get relatedContact
     *
     * @return Contact
     */
    public function getRelatedContact()
    {
        return $this->relatedContact;
    }

    /**
     * Set relatedAccount
     *
     * @param Account $relatedAccount
     * @return Call
     */
    public function setRelatedAccount(Account $relatedAccount = null)
    {
        $this->relatedAccount = $relatedAccount;
    
        return $this;
    }

    /**
     * Get relatedAccount
     *
     * @return Account
     */
    public function getRelatedAccount()
    {
        return $this->relatedAccount;
    }

    /**
     * Set contactPhoneNumber
     *
     * @param ContactPhone $contactPhoneNumber
     * @return Call
     */
    public function setContactPhoneNumber(ContactPhone $contactPhoneNumber = null)
    {
        $this->contactPhoneNumber = $contactPhoneNumber;
    
        return $this;
    }

    /**
     * Get contactPhoneNumber
     *
     * @return ContactPhone
     */
    public function getContactPhoneNumber()
    {
        return $this->contactPhoneNumber;
    }

    /**
     * Set callStatus
     *
     * @param CallStatus $callStatus
     * @return Call
     */
    public function setCallStatus(CallStatus $callStatus = null)
    {
        $this->callStatus = $callStatus;
    
        return $this;
    }

    /**
     * Get callStatus
     *
     * @return CallStatus
     */
    public function getCallStatus()
    {
        return $this->callStatus;
    }
}
