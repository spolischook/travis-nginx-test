<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository")
 * @ORM\Table(name="orob2b_account_user_role",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_account_user_role_account_id_label_idx", columns={
 *              "account_id",
 *              "label"
 *          })
 *      }
 * )
 * @Config(
 *      routeName="orob2b_account_account_user_role_index",
 *      routeUpdate="orob2b_account_account_user_role_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "ownership"={
 *              "frontend_owner_type"="FRONTEND_ACCOUNT",
 *              "frontend_owner_field_name"="account",
 *              "frontend_owner_column_name"="account_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "activity"={
 *              "show_on_page"="\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE"
 *          }
 *      }
 * )
 */
class AccountUserRole extends AbstractRole implements OrganizationAwareInterface
{
    const PREFIX_ROLE = 'ROLE_FRONTEND_';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true, nullable=false)
     */
    protected $role;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *      }
     * )
     */
    protected $label;

    /**
     * @var Website[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinTable(
     *      name="orob2b_account_role_to_website",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_user_role_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $websites;

    /**
     * @var AccountUser[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser", mappedBy="roles")
     */
    protected $accountUsers;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="self_managed", nullable=true)
     */
    protected $selfManaged = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="non_public", nullable=true)
     */
    protected $nonPublic = false;

    /**
     * @param string|null $role
     */
    public function __construct($role = null)
    {
        if ($role) {
            $this->setRole($role, false);
        }

        $this->websites = new ArrayCollection();
        $this->accountUsers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return AccountUserRole
     */
    public function setLabel($label)
    {
        $this->label = (string)$label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return static::PREFIX_ROLE;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account|null $account
     * @return AccountUserRole
     */
    public function setAccount(Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPredefined()
    {
        return !$this->getAccount();
    }

    public function __clone()
    {
        $this->id = null;
        $this->setRole($this->getLabel());
        $this->websites = new ArrayCollection();
        $this->accountUsers = new ArrayCollection();
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return $this
     */
    public function addAccountUser(AccountUser $accountUser)
    {
        if (!$this->accountUsers->contains($accountUser)) {
            $this->accountUsers[] = $accountUser;
        }

        return $this;
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return $this
     */
    public function removeAccountUser(AccountUser $accountUser)
    {
        $this->accountUsers->removeElement($accountUser);

        return $this;
    }

    /**
     * @return Collection|AccountUser[]
     */
    public function getAccountUsers()
    {
        return $this->accountUsers;
    }

    /**
     * @return string
     */
    public function isSelfManaged()
    {
        return (bool)$this->selfManaged;
    }

    /**
     * @param string $selfManaged
     */
    public function setSelfManaged($selfManaged)
    {
        $this->selfManaged = $selfManaged;
    }

    /**
     * @return boolean
     */
    public function isNonPublic()
    {
        return $this->nonPublic;
    }

    /**
     * @param boolean $nonPublic
     */
    public function setNonPublic($nonPublic)
    {
        $this->nonPublic = $nonPublic;
    }
    
    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(
            [
                $this->id,
                $this->role,
                $this->label
            ]
        );
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->role,
            $this->label,
            ) = unserialize($serialized);
    }
}
