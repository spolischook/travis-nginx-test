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

    /**
     * @param ConfigManager $cm
     * @param Mcrypt        $encryptor
     * @param string        $wsdlEndpoint
     * @param bool          $ignoreFailedResponseMessages
     */
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
     * The path to WSDL file describes Exchange Web Services (EWS).
     *
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
     * Gets the password is used to login to the Exchange Server.
     *
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
     * @see EwsType\ExchangeVersionType
     *
     * @return string one of the ExchangeVersionType::* constants
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
     * As an EWS function call may return several response messages, it may be helpful
     * to filter failed ones.
     * If this flag is true the failed response messages are ignored and returned in
     * the EWS function call result together with success response messages.
     * If this flag is false (default behaviour) an exception is thrown is at least one
     * failed response message exists in the result.
     *
     * @return bool
     */
    public function isIgnoreFailedResponseMessages()
    {
        return $this->ignoreFailedResponseMessages;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->cm->get('oro_pro_ews.enabled');
    }
}
