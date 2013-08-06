<?php

namespace OroPro\Bundle\EwsBundle\Ews;

use SoapFault;

/**
 * Exception class for Exchange Web Services
 */
class EwsException extends SoapFault
{
    /**
     * EwsException constructor
     *
     * @param string $faultString The error message
     * @param string $faultCode The error code
     */
    public function __construct(
        $faultString,
        $faultCode = "Sender.Unknown"
    ) {
        parent::__construct($faultCode, $faultString);
    }

    /**
     * @param string $rightPartOfFaultCode
     *
     * @return string
     */
    public static function buildSenderFaultCode($rightPartOfFaultCode)
    {
        return "Sender." . $rightPartOfFaultCode;
    }

    /**
     * @param string $rightPartOfFaultCode
     *
     * @return string
     */
    public static function buildReceiverFaultCode($rightPartOfFaultCode)
    {
        return "Receiver." . $rightPartOfFaultCode;
    }
}
