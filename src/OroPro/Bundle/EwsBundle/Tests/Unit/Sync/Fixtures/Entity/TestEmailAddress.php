<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Sync\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_email_address")
 */
class TestEmailAddress extends EmailAddress
{
    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_user_id", referencedColumnName="id")
     */
    private $owner1;

    public function getOwner()
    {
        return $this->owner1;
    }

    public function setOwner(EmailOwnerInterface $owner = null)
    {
        $this->owner1 = $owner;
        $this->setHasOwner($owner !== null);
    }
}
