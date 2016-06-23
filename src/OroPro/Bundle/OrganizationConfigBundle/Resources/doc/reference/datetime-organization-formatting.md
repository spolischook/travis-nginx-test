Datetime Organization Formatting
============================

Table of Contents
-----------------
  - [Twig Extensions](#twig-extensions)
    - [Formatter filters](#formatter-filters)
    - [oro_format_datetime_organization](#oro_format_datetime_organization)

Twig Extensions
---------------

OroProOrganizationConfigBundle has twig extension that provides formatter filter that allows to get formatted date by organization localization settings.

### Formatter filters

Twig extension DateTimeOrganizationExtension extend DateTimeExtension and has following functions.

#### oro_format_datetime_organization

Proxy for [format](#format) function of DateTimeFormatter, receives datetime value as a first argument
and array of options as a second argument. Allowed options:
  * dateType,
  * timeType,
  * locale,
  * timezone,
  * organization
  
Localization settings are taken from organization configuration. If organization not passed used localization settings from options.

Organization settings:
locale is 'en_US'
time zone is 'America/Los_Angeles'
UTC date is '2016-05-31 00:00:00'
  
```
{{ entity.startDate|oro_format_datetime_organization({'organization': organizationId}) }}
{# May 30, 2016, 5:00 PM #}

{{ entity.startDate|oro_format_datetime({'locale': 'en_US', 'timeZone': 'Europe/Athens'}) }}
{# May 31, 2016, 3:00 AM #}
```
