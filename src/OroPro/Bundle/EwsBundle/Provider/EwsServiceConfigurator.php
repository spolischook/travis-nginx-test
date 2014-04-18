<?php

namespace OroPro\Bundle\EwsBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class EwsServiceConfigurator
{
    /** @var ConfigManager */
    protected $cm;

    /** @var Mcrypt */
    protected $encryptor;

    /** @var string */
    protected $wsdlEndpoint;

    /** @var boolean */
    protected $ignoreFailedResponseMessages;

    public function __construct(
        ConfigManager $cm,
        Mcrypt $encryptor,
        $wsdlEndpoint,
        $ignoreFailedResponseMessages = false
    ) {
        $this->cm                           = $cm;
        $this->encryptor                    = $encryptor;
        $this->wsdlEndpoint                 = $wsdlEndpoint;
        $this->ignoreFailedResponseMessages = $ignoreFailedResponseMessages;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->wsdlEndpoint;
    }

    /**
     * Gets the Exchange Server uri.
     *
     * @return string
     */
    public function getServer()
    {
        return $this->cm->get('oro_pro_ews.server');
    }

    /**
     * Gets the user name is used to login to the Exchange Server.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->cm->get('oro_pro_ews.login');
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->encryptor->decryptData(
            $this->cm->get('oro_pro_ews.password')
        );
    }

    /**
     * Gets the Exchange Server version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->cm->get('oro_pro_ews.version');
    }

    /**
     * @return string[]
     */
    public function getDomains()
    {
        return $this->cm->get('oro_pro_ews.domain_list');
    }

    /**
     * @return bool
     */
    public function isIgnoreFailedResponseMessages()
    {
        return $this->ignoreFailedResponseMessages;
    }
}
