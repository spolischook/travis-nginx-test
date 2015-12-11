<?php

namespace OroPro\Bundle\EwsBundle\Provider;

/**
 * Class EwsTransportCheckServiceConfigurator
 * @package OroPro\Bundle\EwsBundle\Provider
 */
class EwsTransportCheckServiceConfigurator extends EwsServiceConfigurator
{
    /**
     * @var string
     */
    protected $server;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $login;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $domains;

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @param $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @param $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param $domains
     */
    public function setDomains($domains)
    {
        $this->domains = $domains;
    }
}
