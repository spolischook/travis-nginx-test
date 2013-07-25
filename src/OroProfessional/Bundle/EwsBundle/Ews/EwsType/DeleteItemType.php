<?php

namespace OroProfessional\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * DeleteItemType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class DeleteItemType extends BaseRequestType
{
    /**
     * @var NonEmptyArrayOfBaseItemIdsType
     * @access public
     */
    public $ItemIds;

    /**
     * @var string
     * @see DisposalType
     * @access public
     */
    public $DeleteType;

    /**
     * @var string
     * @see CalendarItemCreateOrDeleteOperationType
     * @access public
     */
    public $SendMeetingCancellations;

    /**
     * @var string
     * @see AffectedTaskOccurrencesType
     * @access public
     */
    public $AffectedTaskOccurrences;
}
// @codingStandardsIgnoreEnd
