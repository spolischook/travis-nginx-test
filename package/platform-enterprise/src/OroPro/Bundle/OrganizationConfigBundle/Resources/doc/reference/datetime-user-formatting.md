Datetime User Formatting
============================

Table of Contents
-----------------
  - [Twig Extensions](#twig-extensions)
    - [Formatter filters](#formatter-filters)
    - [oro_format_datetime_user](#oro_format_datetime_user)
    - [Format Converter functions](#format-converter-functions)
    - [calendar_date_range_user](#calendar_date_range_user)

Twig Extensions
---------------

OroProOrganizationConfigBundle has twig extension that provides formatter filter that allows to get formatted date by user organization localization settings.

### Formatter filters

Twig extension DateTimeUserExtension extend DateTimeExtension and has following functions.

#### oro_format_datetime_user

Proxy for [format](#format) function of DateTimeFormatter, receives datetime value as a first argument
and array of options as a second argument. Allowed options:
  * dateType,
  * timeType,
  * locale,
  * timezone,
  * user

Localization settings are taken from user organization configuration. If user not passed used localization settings from options.

Organization settings:
locale is 'en_US'
time zone is 'America/Los_Angeles'
UTC date is '2016-05-31 00:00:00'

```
{{ entity.startDate|oro_format_datetime_user({'user': user}) }}
{# May 30, 2016, 5:00 PM #}

{{ entity.startDate|oro_format_datetime({'locale': 'en_US', 'timeZone': 'Europe/Athens'}) }}
{# May 31, 2016, 3:00 AM #}
```
### Format Converter functions

Twig extension DateTimeUserExtension has following functions.

#### calendar_date_range_user

Returns a string represents a range between $startDate and $endDate, formatted according the given user organization settings
Allowed options:
  * startDate,
  * endDate,
  * skipTime,
  * dateType,
  * timeType,
  * locale,
  * timezone,
  * user

UTC date range is '28.06.2016 00:00:00' - '28.06.2016 00:30:00'

```
{{ calendar_date_range_user(entity.start, entity.end, entity.allDay, 1, null, null, null, user) }}
{# May 30, 2016 5:00 PM - 5:30 PM #}
```
