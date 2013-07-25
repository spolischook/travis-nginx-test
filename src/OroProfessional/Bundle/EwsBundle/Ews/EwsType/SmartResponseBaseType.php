<?php

namespace OroProfessional\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * SmartResponseBaseType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class SmartResponseBaseType
{
    /**
     * @var string
     * @access public
     */
    public $Subject;

    /**
     * @var BodyType
     * @access public
     */
    public $Body;

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
