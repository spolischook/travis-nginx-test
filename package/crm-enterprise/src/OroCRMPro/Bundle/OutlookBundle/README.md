OroCRM Outlook Bundle
=====================

This bundle provides a functionality to work with Microsoft Outlook.

How to deploy Outlook Add-In
----------------------------

The Outlook Add-In installer and corresponding release notes should be located in `src/OroCRMPro/Bundle/OutlookBundle/Resources/public/files` folder. The installer file should have a name that follows next pattern `OroCRMOutlookAddIn_{version}.exe`. If there is a file with release notes it should be located in the same folder and should have a name that follows the next pattern `OroCRMOutlookAddIn_{version}.md`. Also this folder can contains `config.yml` file where you can specify the minimal version of the Outlook Add-In that is supported by the current version of the server. Here is an example of `config.yml`:

```yaml
min_supported_version: '1.5'
``` 

During deployment of CRM application the files from this folder will be automatically copied to `/web/bundles/orocrmprooutlook/files` to be visible in public space.

If a new version of Outlook Add-In is deployed together with CRM application the described actions are enough.

But if you need to deploy only the add-in without deploying whole CRM application you need to do the following steps:
- copy Outlook Add-In installer and corresponding release notes to both `src/OroCRMPro/Bundle/OutlookBundle/Resources/public/files` and `/web/bundles/orocrmprooutlook/files` folders.
- modify `config.yml` in both of these folders.
- run `php app/console oro:outlook:cache:clear` CLI command to invalidate the add-n related caches.

Outlook Add-In configuration accessible through REST API
--------------------------------------------------------

The Outlook Add-In configuration can be retrieved by the following REST API resource:

```
GET /api/rest/latest/configuration/outlook.json
```

This resource returns the following configuration data:

- **oro_crm_pro_outlook.orocrm_version** *string* - The OroCRM version.
- **oro_crm_pro_outlook.addin_latest_version** *string* *optional* - The latest available version of the Outlook Add-In.
- **oro_crm_pro_outlook.addin_latest_version_url** *string* *optional* - The URL to the latest available version of the Outlook Add-In installer.
- **oro_crm_pro_outlook.addin_latest_version_doc_url** *string* *optional* - The URL to the release notes for the latest version of the Outlook Add-In.
- **oro_crm_pro_outlook.addin_min_supported_version** *string* *optional* - The minimal version of the Outlook Add-In that is supported by this server.
- **oro_crm_pro_outlook.contacts_enabled** *boolean* - Indicates whether a synchronization of contacts is enabled.
- **oro_crm_pro_outlook.contacts_sync_direction** *string* - The direction of contacts synchronization. Can be: **OroCRMToOutlook**, **OutlookToOroCRM** or **Both**.
- **oro_crm_pro_outlook.contacts_conflict_resolution** *string* - The strategy that should be used to solve contacts synchronization conflicts if the same contact is changed in both Outlook and CRM. Can be: **OroCRMAlwaysWins** or **OutlookAlwaysWins**.
- **oro_crm_pro_outlook.contacts_sync_interval_orocrm** *integer* - Determines how often contacts changes on CRM side should be checked. The value is in seconds.
- **oro_crm_pro_outlook.contacts_sync_interval_outlook** *integer* - Determines how often contacts changes on Outlook side should be checked. The value is in seconds.
- **oro_crm_pro_outlook.contacts_keys** *array* of strings - The list of fields that should be used to unique identify a contact.
- **oro_crm_pro_outlook.contacts_mapping** *array* - A map between contact's fields in OroCRM and Outlook. 
- **oro_crm_pro_outlook.tasks_enabled** *boolean* - Indicates whether a synchronization of tasks is enabled.
- **oro_crm_pro_outlook.calendar_events_enabled** *boolean* - Indicates whether a synchronization of calendar events is enabled.
- **oro_crm_pro_outlook.side_bar_panel_layout** *string* - The layout of the side bar panel for the Outlook Add-In in XAML format.
- **oro_crm_pro_outlook.create_lead_dialog_layout** *string* - The layout of the create lead dialog for the Outlook Add-In in XAML format.
- **oro_crm_pro_outlook.create_opportunity_dialog_layout** *string* - The layout of the create opportunity dialog for the Outlook Add-In in XAML format.
- **oro_crm_pro_outlook.create_case_dialog_layout** *string* - The layout of the create case dialog for the Outlook Add-In in XAML format.
