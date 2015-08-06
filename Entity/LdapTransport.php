<?php
namespace OroCRMPro\Bundle\LDAPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * Class LdapTransport
 *
 * @package OroCRMPro\Bundle\LDAPBundle\Entity
 *
 * @ORM\Entity
 */
class LdapTransport extends Transport
{
    const DEFAULT_PORT = 636;
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_ENCRYPTION = 'tls';
    const DEFAULT_BASE_DN = 'dc=localhost,dc=com';
    const DEFAULT_USERNAME = 'cn=admin,dc=localhost,dc=com';

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_host", type="string")
     */
    protected $host;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_port", type="integer")
     */
    protected $port;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_encryption", type="string")
     */
    protected $encryption;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_base_dn", type="string")
     */
    protected $baseDn;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_username", type="string")
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_password", type="string")
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_account_domain", type="string")
     */
    protected $accountDomainName;

    /**
     * @var string
     *
     * @ORM\Column(name="ldap_account_domain_short", type="string")
     */
    protected $accountDomainNameShort;

    /** @var ParameterBag */
    protected $settings;

    public function __construct()
    {
        $this->host = self::DEFAULT_HOST;
        $this->port = self::DEFAULT_PORT;
        $this->encryption = self::DEFAULT_ENCRYPTION;
        $this->baseDn = self::DEFAULT_BASE_DN;
        $this->username = self::DEFAULT_USERNAME;
    }

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'host'                   => $this->getHost(),
                    'port'                   => $this->getPort(),
                    'encryption'             => $this->getEncryption(),
                    'baseDn'                 => $this->getBaseDn(),
                    'username'               => $this->getUsername(),
                    'password'               => $this->getPassword(),
                    'accountDomainName'      => $this->getAccountDomainName(),
                    'accountDomainNameShort' => $this->getAccountDomainNameShort(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncryption()
    {
        return $this->encryption;
    }

    /**
     * @param string $encryption
     *
     * @return $this
     */
    public function setEncryption($encryption)
    {
        $this->encryption = $encryption;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * @param string $baseDn
     *
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->baseDn = $baseDn;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        if (empty($password)) {
            return $this;
        }

        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountDomainName()
    {
        return $this->accountDomainName;
    }

    /**
     * @param string $accountDomainName
     *
     * @return $this
     */
    public function setAccountDomainName($accountDomainName)
    {
        $this->accountDomainName = $accountDomainName;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountDomainNameShort()
    {
        return $this->accountDomainNameShort;
    }

    /**
     * @param string $accountDomainNameShort
     *
     * @return $this
     */
    public function setAccountDomainNameShort($accountDomainNameShort)
    {
        $this->accountDomainNameShort = $accountDomainNameShort;

        return $this;
    }
}
