<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\SaleBundle\Model\ExtendQuoteAddress;

/**
 * @ORM\Table("orob2b_quote_address")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *       defaultValues={
 *          "entity"={
 *              "icon"="icon-map-marker"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @ORM\Entity
 */
class QuoteAddress extends ExtendQuoteAddress
{
    /**
     * @var AccountAddress
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountAddress")
     * @ORM\JoinColumn(name="account_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $accountAddress;

    /**
     * @var AccountUserAddress
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress")
     * @ORM\JoinColumn(name="account_user_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $accountUserAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "entity"={
     *          "contact_information"="phone"
     *      }
     *  }
     * )
     */
    protected $phone;

    /**
     * Set accountAddress
     *
     * @param AccountAddress|null $accountAddress
     *
     * @return QuoteAddress
     */
    public function setAccountAddress(AccountAddress $accountAddress = null)
    {
        $this->accountAddress = $accountAddress;

        return $this;
    }

    /**
     * Get accountUserAddress
     *
     * @return AccountAddress|null
     */
    public function getAccountAddress()
    {
        return $this->accountAddress;
    }

    /**
     * Set accountUserAddress
     *
     * @param AccountUserAddress|null $accountUserAddress
     *
     * @return QuoteAddress
     */
    public function setAccountUserAddress(AccountUserAddress $accountUserAddress = null)
    {
        $this->accountUserAddress = $accountUserAddress;

        return $this;
    }

    /**
     * Get accountUserAddress
     *
     * @return AccountUserAddress|null
     */
    public function getAccountUserAddress()
    {
        return $this->accountUserAddress;
    }

    /**
     * Set phone number
     *
     * @param string $phone
     *
     * @return QuoteAddress
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
