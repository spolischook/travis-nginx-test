<?php

namespace OroProfessional\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * WeeklyRecurrencePatternType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class WeeklyRecurrencePatternType extends IntervalRecurrencePatternBaseType
{
    /**
     * @var string can contain multiple values (values are separated by a space character) of DayOfWeekType
     * @see DayOfWeekType
     * @access public
     */
    public $DaysOfWeek;

    /**
     * @var string
     * @see DayOfWeekType
     * @access public
     */
    public $FirstDayOfWeek;
}
// @codingStandardsIgnoreEnd
