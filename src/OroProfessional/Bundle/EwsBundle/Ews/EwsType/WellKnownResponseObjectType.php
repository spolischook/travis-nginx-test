<?php

namespace OroProfessional\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * WellKnownResponseObjectType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class WellKnownResponseObjectType
{
    /**
     * @var string WSDL type is ItemClassType
     * @access public
     */
    public $ItemClass;

    /**
     * @var string
     * @see SensitivityChoicesType
     * @access public
     */
    public $Sensitivity;

    /**
     * @var BodyType
     * @access public
     */
    public $Body;

    /**
     * @var NonEmptyArrayOfAttachmentsType
     * @access public
     */
    public $Attachments;

    /**
     * @var NonEmptyArrayOfInternetHeadersType
     * @access public
     */
    public $InternetMessageHeaders;

    /**
     * @var SingleRecipientType
     * @access public
     */
    public $Sender;

    /**
     * @var ArrayOfRecipientsType
     * @access public
     */
    public $ToRecipients;

    /**
     * @var ArrayOfRecipientsType
     * @access public
     */
    public $CcRecipients;

    /**
     * @var ArrayOfRecipientsType
     * @access public
     */
    public $BccRecipients;

    /**
     * @var boolean
     * @access public
     */
    public $IsReadReceiptRequested;

    /**
     * @var boolean
     * @access public
     */
    public $IsDeliveryReceiptRequested;

    /**
     * @var SingleRecipientType
     * @access public
     */
    public $From;

    /**
     * @var ItemIdType
     * @access public
     */
    public $ReferenceItemId;

    /**
     * @var string
     * @access public
     */
    public $ObjectName;
}
// @codingStandardsIgnoreEnd
