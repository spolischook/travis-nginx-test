Datetime Organization Formatting
============================

Table of Contents
-----------------
  - [Twig Extensions](#twig-extensions)
    - [Formatter filters](#formatter-filters)
    - [calendar_date_range_organization](#calendar_date_range_organization)

Twig Extensions
---------------

OroProLocaleBundle has twig extension that provides formatter filter that allows to get formatted date by organization localization settings.

### Formatter filters

DateRangeFormatOrganizationExtension extend twig extension from platform and has following functions.

#### calendar_date_range_organization

Returns a string represents a range between $startDate and $endDate, formatted according the given organization settings
Allowed options:
  * startDate,
  * endDate,
  * skipTime,
  * dateType,
  * timeType,
  * locale,
  * timezone,
  * organization

UTC date range is '28.06.2016 00:00:00' - '28.06.2016 00:30:00'

```
{{ calendar_date_range_organization(entity.start, entity.end, entity.allDay, 1, null, null, null, entity.organization) }}
{# May 30, 2016 5:00 PM - 5:30 PM #}
```
