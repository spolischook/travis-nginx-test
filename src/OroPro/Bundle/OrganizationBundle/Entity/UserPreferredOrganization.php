<?php

namespace OroPro\Bundle\OrganizationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *    name="oro_pro_organization_user_pref",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="oro_pro_organization_usrorg_uq",
 *          columns={"user_id", "organization_id"}
 *      )
 * })
 */
class UserPreferredOrganization
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $organization;

    /**
     * @param User         $user
     * @param Organization $organization
     */
    public function __construct(User $user, Organization $organization)
    {
        $this->user         = $user;
        $this->organization = $organization;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
