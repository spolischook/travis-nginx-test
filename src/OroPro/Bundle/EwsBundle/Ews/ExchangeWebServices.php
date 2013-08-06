<?php

namespace OroPro\Bundle\EwsBundle\Ews;

use OroPro\Bundle\EwsBundle\Ews\AbstractExchangeWebServices;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use SoapClient;

/**
 * The ExchangeWebServices class provides a SOAP client
 */
class ExchangeWebServices extends AbstractExchangeWebServices
{
    /**
     * Location of the Exchange server.
     *
     * @var string
     */
    protected $server;

    /**
     * Username to use when connecting to the Exchange server.
     *
     * @var string
     */
    protected $username;

    /**
     * Password to use when connecting to the Exchange server.
     *
     * @var string
     */
    protected $password;

    /**
     * Miscrosoft Exchange Server version that we are going to connect to
     *
     * @var string one of the ExchangeVersionType::* constants
     * @see EwsType\ExchangeVersionType
     */
    protected $version;

    /**
     * The path to WSDL file describes Exchange Web Services (EWS).
     *
     * @var string
     */
    protected $wsdlFile;

    /**
     * SOAP client used to make the request
     *
     * @var ExchangeSoapClient
     */
    protected $soap = null;

    /**
     * Exchange impersonation
     *
     * @var EWSType\ExchangeImpersonationType
     */
    protected $impersonation = null;

    /**
     * As an EWS function call may return several response messages, it may be helpful
     * to filter failed ones.
     * If this flag is true the failed response messages are ignored and returned in
     * the EWS function call result together with success response messages.
     * If this flag is false (default behaviour) an exception is thrown is at least one
     * failed response message exists in the result.
     *
     * @var bool
     */
    protected $ignoreFailedResponseMessages = false;

    /**
     * Constructor for the ExchangeWebServices class
     *
     * @param string $wsdlFile
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $version one of the ExchangeVersionType::* constants
     * @param bool $ignoreFailedResponseMessages
     * @see EwsType\ExchangeVersionType
     */
    public function __construct(
        $wsdlFile,
        $server = null,
        $username = null,
        $password = null,
        $version = EwsType\ExchangeVersionType::EXCHANGE2007,
        $ignoreFailedResponseMessages = false
    ) {
        $this->wsdlFile = $wsdlFile;
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->version = $version;
        $this->ignoreFailedResponseMessages = $ignoreFailedResponseMessages;
    }

    /**
     * Sets the impersonation property.
     *
     * @param EWSType\ExchangeImpersonationType $impersonation
     */
    public function setImpersonation($impersonation)
    {
        $this->impersonation = $impersonation;
    }

    /**
     * Gets the the Exchange Server version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets the the Exchange Server version.
     *
     * @param string $version one of the ExchangeVersionType::* constants
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Initializes the SOAP client to make a request
     *
     * @return SoapClient
     */
    protected function initializeSoapClient()
    {
        $options = array();

        if ($this->server != null) {
            $options['location'] = 'https://' . $this->server . '/EWS/Exchange.asmx';
        }
        if ($this->version != null) {
            $options['version'] = $this->version;
        }
        if ($this->username != null) {
            $options['user'] = $this->username;
        }
        if ($this->username != null) {
            $options['password'] = $this->password;
        }

        // To create arrays even if a single element returned
        // see https://bugs.php.net/bug.php?id=36226
        $options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;

        $this->UpdateClassmapOption($options);

        if ($this->impersonation != null) {
            $options['impersonation'] = $this->impersonation;
        }

        return new ExchangeSoapClient($this->wsdlFile, $options);
    }

    /**
     * Makes a SOAP call
     *
     * @param string $functionName The name of the SOAP function to call
     * @param array $arguments An array of the arguments to pass to the function
     * @param array $options [optional] An associative array of options to pass to the client
     *        The location option is the URL of the remote Web service
     *        The uri option is the target namespace of the SOAP service
     *        The soapaction option is the action to call
     * @param mixed $input_headers [optional] An array of headers to be sent along with the SOAP request
     * @param array $output_headers [optional] If supplied, this array will be filled with the headers from the SOAP response
     *
     * @return mixed
     */
    protected function soapCall(
        $functionName,
        array $arguments,
        array $options = null,
        $input_headers = null,
        array &$output_headers = null
    ) {
        $this->soap = $this->InitializeSoapClient();
        $response = $this->soap->__soapCall($functionName, $arguments, $options, $input_headers, $output_headers);
        return $this->ProcessResponse($response);
    }

    /**
     * Process a response to verify that it succeeded and take the appropriate action
     *
     * @param mixed $response
     * @return mixed The typified object
     * @throws EwsException
     */
    protected function processResponse($response)
    {
        // If the soap call failed then we need to throw an exception.
        $this->validateResponseCode($this->soap->getResponseCode());

        // If the soap call returns at least one fail message then we need to throw an exception.
        $this->validateResponseType($response);

        return $response;
    }

    /**
     * @param $code
     * @throws EwsException
     */
    private function validateResponseCode($code)
    {
        if ($code != 200) {
            $err = (string)$code;
            $faultCode = EwsException::buildReceiverFaultCode("Soap.RequestProcessingFailed");
            // Process the most common errors
            if ($code == 400) {
                $err = "Bad request";
            } elseif ($code == 401) {
                $err = "Unauthorized";
                $faultCode = EwsException::buildReceiverFaultCode("Soap.Unauthorized");
            } elseif ($code == 403) {
                $err = "Forbidden";
                $faultCode = EwsException::buildReceiverFaultCode("Soap.Forbidden");
            } elseif ($code == 500) {
                $err = "Internal Error";
            } elseif ($code == 501) {
                $err = "Not implemented";
            }
            throw new EwsException('SOAP client returned status of [' . $err . ']', $faultCode);
        }
    }

    /**
     * @param mixed $response
     * @throws EwsException
     */
    private function validateResponseType($response)
    {
        if ($response != null && is_object($response)) {
            if (get_class($response) == "stdClass") {
                throw new EwsException(
                    "SOAP client returns a response as 'stdClass' class, but it is expected more precise response type."
                    . "Please check that SOAP client is configured properly.",
                    EwsException::buildSenderFaultCode("Ews.InvalidConfiguration"));
            }
            if (property_exists($response, "ResponseMessages")) {
                $responseMessagesCount = 0;
                $failedMessages = array();
                foreach (get_object_vars($response->ResponseMessages) as $messages) {
                    if (is_array($messages)) {
                        foreach ($messages as $message) {
                            if (property_exists($message, "ResponseClass")) {
                                $responseMessagesCount++;
                                if ($message->ResponseClass == "Error") {
                                    $failedMessages[] = array(
                                        'ResponseCode' => $message->ResponseCode,
                                        'MessageText' => $message->MessageText
                                    );
                                }
                            }
                        }
                    }
                }
                if (!$this->ignoreFailedResponseMessages && count($failedMessages) > 0) {
                    throw new EwsException(
                        $failedMessages[0]['MessageText'],
                        EwsException::buildReceiverFaultCode("Ews." . $failedMessages[0]['ResponseCode']));
                }
            }
        }
    }

    /**
     * Determines if the QueryString property of the find item request is supported by
     * current Exchange Server version.
     *
     * @return bool
     */
    public function isQueryStringSupported()
    {
        $isExchange2007 = (
            $this->version == EwsType\ExchangeVersionType::EXCHANGE2007
            || $this->version == EwsType\ExchangeVersionType::EXCHANGE2007_SP1);
        return !$isExchange2007;
    }
}
