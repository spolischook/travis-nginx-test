<?php

namespace Oro\Bundle\LDAPBundle\Model;

use FR3D\LdapBundle\Model\LdapUserInterface;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User as OroUser;

class User implements LdapUserInterface, UserInterface
{
    /** @var string */
    protected $dn;

    /** @var string */
    protected $username;

    /** @var string */
    protected $salt;

    /** @var string */
    protected $password;

    /** @var array */
    protected $roles = [];

    /**
     * {@inheritdoc}
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * {@inheritdoc}
     */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->password = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Creates model user from Oro user.
     *
     * @param OroUser $oroUser
     * @param integer $channelId
     *
     * @return $this
     */
    public static function createFromUser(OroUser $oroUser, $channelId)
    {
        $mappings = (array)$oroUser->getLdapMappings();

        $user = new static();
        $user->dn       = isset($mappings[$channelId]) ? $mappings[$channelId] : null;
        $user->username = $oroUser->getUsername();
        $user->roles    = $oroUser->getRoles();
        $user->salt     = $oroUser->getSalt();
        $user->password = $oroUser->getPassword();

        return $user;
    }
}
