<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_account_user")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="organizations",
 *          joinTable=@ORM\JoinTable(
 *              name="orob2b_account_user_org",
 *              joinColumns={
 *                  @ORM\JoinColumn(
 *                      name="account_user_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE"
 *                  )
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="organization_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE"
 *                  )
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      routeName="orob2b_account_account_user_index",
 *      routeView="orob2b_account_account_user_view",
 *      routeUpdate="orob2b_account_account_user_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id",
 *              "frontend_owner_type"="FRONTEND_ACCOUNT",
 *              "frontend_owner_field_name"="account",
 *              "frontend_owner_column_name"="account_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "form"={
 *              "form_type"="orob2b_account_account_user_select",
 *              "grid_name"="account-account-user-select-grid"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AccountUser extends AbstractUser implements FullNameInterface, EmailHolderInterface, CustomerUserIdentity
{
    const SECURITY_GROUP = 'commerce';

    /**
     * @var AccountUserRole[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUserRole", inversedBy="accountUsers")
     * @ORM\JoinTable(
     *      name="orob2b_acc_user_access_role",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_user_role_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $roles;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account",
     *      inversedBy="users",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $account;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $confirmed = true;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $email;

    /**
     * Name prefix
     *
     * @var string
     *
     * @ORM\Column(name="name_prefix", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $namePrefix;

    /**
     * First name
     *
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $firstName;

    /**
     * Middle name
     *
     * @var string
     *
     * @ORM\Column(name="middle_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $middleName;

    /**
     * Last name
     *
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $lastName;

    /**
     * Name suffix
     *
     * @var string
     *
     * @ORM\Column(name="name_suffix", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $nameSuffix;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $birthday;

    /**
     * @var Collection|AccountUserAddress[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress",
     *      mappedBy="frontendOwner",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"primary" = "DESC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $addresses;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinTable(
     *      name="orob2b_account_user_sales_reps",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $salesRepresentatives;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var ArrayCollection|AccountUserCurrency[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUserCurrency",
     *      mappedBy="accountUser",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->salesRepresentatives = new ArrayCollection();
        $this->currencies = new ArrayCollection();
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->password,
                $this->salt,
                $this->username,
                $this->enabled,
                $this->confirmed,
                $this->confirmationToken,
                $this->id
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->password,
            $this->salt,
            $this->username,
            $this->enabled,
            $this->confirmed,
            $this->confirmationToken,
            $this->id
            ) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return sprintf('%s %s', $this->getFirstName(), $this->getLastName());
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return AccountUser
     */
    public function setAccount(Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @param string|null $companyName
     */
    public function createAccount($companyName = null)
    {
        if (!$this->account) {
            $this->account = new Account();
            $this->account->setOrganization($this->organization);

            if (!$companyName) {
                $companyName = sprintf('%s %s', $this->firstName, $this->lastName);
            }

            $this->account->setName($companyName);

            if ($this->getOwner() && !$this->account->getOwner()) {
                $this->account->setOwner($this->getOwner(), false);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountNonLocked()
    {
        return $this->isEnabled() && $this->isConfirmed();
    }

    /**
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     *
     * @return AbstractUser
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = (bool)$confirmed;

        return $this;
    }

    /**
     * @param string $username
     * @return AccountUser
     */
    public function setUsername($username)
    {
        parent::setUsername($username);

        $this->email = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return AccountUser
     */
    public function setEmail($email)
    {
        $this->email = $email;
        $this->username = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * @param string $namePrefix
     * @return AccountUser
     */
    public function setNamePrefix($namePrefix)
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return AccountUser
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @param string $middleName
     * @return AccountUser
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return AccountUser
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * @param string $nameSuffix
     * @return AccountUser
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday
     * @return AccountUser
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Add addresses
     *
     * @param AbstractDefaultTypedAddress $address
     * @return AccountUser
     */
    public function addAddress(AbstractDefaultTypedAddress $address)
    {
        /** @var AbstractDefaultTypedAddress $address */
        if (!$this->getAddresses()->contains($address)) {
            $this->getAddresses()->add($address);
            $address->setFrontendOwner($this);
            $address->setSystemOrganization($this->getOrganization());

            if ($this->getOwner()) {
                $address->setOwner($this->getOwner());
            }
        }

        return $this;
    }

    /**
     * Remove addresses
     *
     * @param AbstractDefaultTypedAddress $addresses
     * @return AccountUser
     */
    public function removeAddress(AbstractDefaultTypedAddress $addresses)
    {
        if ($this->hasAddress($addresses)) {
            $this->getAddresses()->removeElement($addresses);
        }

        return $this;
    }

    /**
     * Get addresses
     *
     * @return Collection
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param AbstractDefaultTypedAddress $address
     * @return bool
     */
    protected function hasAddress(AbstractDefaultTypedAddress $address)
    {
        return $this->getAddresses()->contains($address);
    }

    /**
     * Gets one address that has specified type name.
     *
     * @param string $typeName
     *
     * @return AbstractDefaultTypedAddress|null
     */
    public function getAddressByTypeName($typeName)
    {
        /** @var AbstractDefaultTypedAddress $address */
        foreach ($this->getAddresses() as $address) {
            if ($address->hasTypeWithName($typeName)) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Gets primary address if it's available.
     *
     * @return AbstractDefaultTypedAddress|null
     */
    public function getPrimaryAddress()
    {
        /** @var AbstractDefaultTypedAddress $address */
        foreach ($this->getAddresses() as $address) {
            if ($address->isPrimary()) {
                return $address;
            }
        }

        return null;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return AccountUser
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        foreach ($this->addresses as $accountUserAddress) {
            $accountUserAddress->setOwner($owner);
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getSalesRepresentatives()
    {
        return $this->salesRepresentatives;
    }

    /**
     * @param User $salesRepresentative
     * @return $this
     */
    public function addSalesRepresentative(User $salesRepresentative)
    {
        if (!$this->salesRepresentatives->contains($salesRepresentative)) {
            $this->salesRepresentatives->add($salesRepresentative);
        }

        return $this;
    }

    /**
     * @param User $salesRepresentative
     * @return $this
     */
    public function removeSalesRepresentative(User $salesRepresentative)
    {
        if ($this->salesRepresentatives->contains($salesRepresentative)) {
            $this->salesRepresentatives->removeElement($salesRepresentative);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return AccountUser
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return AccountUser
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @param Website $website
     * @return null|AccountUserCurrency
     */
    public function getCurrencyByWebsite(Website $website)
    {
        foreach ($this->currencies as $currency) {
            if ($currency->getWebsite() === $website) {
                return $currency;
            }
        }

        return null;
    }

    /**
     * @param Website $website
     * @param string $currency
     * @return $this
     */
    public function setWebsiteCurrency(Website $website, $currency)
    {
        $userCurrency = $this->getCurrencyByWebsite($website);

        if (null === $userCurrency) {
            $userCurrency = new AccountUserCurrency();
            $this->currencies->add($userCurrency);
        }

        $userCurrency->setWebsite($website)
            ->setWebsite($website)
            ->setAccountUser($this)
            ->setCurrency($currency);

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->loginCount = 0;

        $this->createAccount();
    }

    /**
     * Invoked before the entity is updated.
     *
     * @ORM\PreUpdate
     *
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $excludedFields = ['lastLogin', 'loginCount'];

        if (array_diff_key($event->getEntityChangeSet(), array_flip($excludedFields))) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }

    /**
     * @todo: Replace with implementation from BB-3247
     * @param Website $website
     * @return string
     */
    public function getCurrencyByWebsite(Website $website)
    {
        return 'USD';
    }

    /**
     * @todo: Replace with implementation from BB-3247
     * @param Website $website
     * @param string $currency
     * @return $this
     */
    public function setWebsiteCurrency(Website $website, $currency)
    {
        return $this;
    }
}
