<?php

namespace OroProfessional\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * MeetingRequestMessageType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class MeetingRequestMessageType extends MeetingMessageType
{
    /**
     * @var string
     * @see MeetingRequestTypeType
     * @access public
     */
    public $MeetingRequestType;

    /**
     * @var string
     * @see LegacyFreeBusyType
     * @access public
     */
    public $IntendedFreeBusyStatus;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $Start;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $End;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $OriginalStart;

    /**
     * @var boolean
     * @access public
     */
    public $IsAllDayEvent;

    /**
     * @var string
     * @see LegacyFreeBusyType
     * @access public
     */
    public $LegacyFreeBusyStatus;

    /**
     * @var string
     * @access public
     */
    public $Location;

    /**
     * @var string
     * @access public
     */
    public $When;

    /**
     * @var boolean
     * @access public
     */
    public $IsMeeting;

    /**
     * @var boolean
     * @access public
     */
    public $IsCancelled;

    /**
     * @var boolean
     * @access public
     */
    public $IsRecurring;

    /**
     * @var boolean
     * @access public
     */
    public $MeetingRequestWasSent;

    /**
     * @var string
     * @see CalendarItemTypeType
     * @access public
     */
    public $CalendarItemType;

    /**
     * @var string
     * @see ResponseTypeType
     * @access public
     */
    public $MyResponseType;

    /**
     * @var SingleRecipientType
     * @access public
     */
    public $Organizer;

    /**
     * @var NonEmptyArrayOfAttendeesType
     * @access public
     */
    public $RequiredAttendees;

    /**
     * @var NonEmptyArrayOfAttendeesType
     * @access public
     */
    public $OptionalAttendees;

    /**
     * @var NonEmptyArrayOfAttendeesType
     * @access public
     */
    public $Resources;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $ConflictingMeetingCount;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $AdjacentMeetingCount;

    /**
     * @var NonEmptyArrayOfAllItemsType
     * @access public
     */
    public $ConflictingMeetings;

    /**
     * @var NonEmptyArrayOfAllItemsType
     * @access public
     */
    public $AdjacentMeetings;

    /**
     * @var string
     * @access public
     */
    public $Duration;

    /**
     * @var string
     * @access public
     */
    public $TimeZone;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $AppointmentReplyTime;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $AppointmentSequenceNumber;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $AppointmentState;

    /**
     * @var RecurrenceType
     * @access public
     */
    public $Recurrence;

    /**
     * @var OccurrenceInfoType
     * @access public
     */
    public $FirstOccurrence;

    /**
     * @var OccurrenceInfoType
     * @access public
     */
    public $LastOccurrence;

    /**
     * @var NonEmptyArrayOfOccurrenceInfoType
     * @access public
     */
    public $ModifiedOccurrences;

    /**
     * @var NonEmptyArrayOfDeletedOccurrencesType
     * @access public
     */
    public $DeletedOccurrences;

    /**
     * @var TimeZoneType
     * @access public
     */
    public $MeetingTimeZone;

    /**
     * @var TimeZoneDefinitionType
     * @access public
     */
    public $StartTimeZone;

    /**
     * @var TimeZoneDefinitionType
     * @access public
     */
    public $EndTimeZone;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $ConferenceType;

    /**
     * @var boolean
     * @access public
     */
    public $AllowNewTimeProposal;

    /**
     * @var boolean
     * @access public
     */
    public $IsOnlineMeeting;

    /**
     * @var string
     * @access public
     */
    public $MeetingWorkspaceUrl;

    /**
     * @var string
     * @access public
     */
    public $NetShowUrl;
}
// @codingStandardsIgnoreEnd
