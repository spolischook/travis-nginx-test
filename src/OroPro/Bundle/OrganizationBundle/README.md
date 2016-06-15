OroProOrganizationBundle
=====================
The main goals of the `OroProOrganizationBundle` could be described as the following:

- The `OroProOrganizationBundle` introduced CRUD for `OroOrganizationBundle`
- Filtering the following data by concrete organization:
    - Custom entities
    - Extended entities fields

Organization selector
---------------------
Appears at edit custom entity page and edit custom field page and checked to "All" by default.

In first case you will be offered all enabled organization.

When you edit a field, available organization selector values will be restricted to the entity's organizations. This behaviour provided by `OrganizationConfigType`

Rendering Menus
---------------
As custom entities could be set up into particular organization it is necessary to remove them from main navigation menu.

They appear at **System/Entities**, **Reports & Segments**. Such behavior achieved by listeners placed within EventListener directory.
 
- `EntityNavigationListener` Allows to exclude entities which outside of the current organization from navigation menu (System/Entities). 
It overrides NavigationListener from OroEntityBundle to achieve this.

- `ReportNavigationListener` In case when report has created via custom entity, that later was moved into another organization, we exclude it from navigation menu (Reports & Segments). 
This behavior reaches by inheriting NavigationListener from OroReportBundle and overriding checkAvailability() method.

Building Forms
--------------
The number of fields within **Create / Edit the entity instance** form varies. It depends on which organization they are.

Custom fields are limited by `OrganizationExclusionProvider`, fields assigned into another organization will be ignored.

Building Datagrid
-----------------
As custom fields could be set up into particular organization they should be displayed per organization within the datagrid.

`DynamicFieldsExtension` extends `DynamicFieldsExtension` from `OroEntityBundle` and filters fields for concrete organization.

Controls access to some objects
-------------------------------
Report or Segment could be created using some custom entity. It makes sense to restrict access to them in case, the entity was moved into another organization.

It's done by `RequestReportParamConverter` that checks whether requested report or segment (rather the entity) is available for the current organization.


System access organization
--------------------------

 By default, user can not see records from another organizations. To have ability to see all the records from all 
 organizations, in organization edit page there is option `System access`. This option 
 allow to set organization as `System Access Organization`.
 
 If user will switch to this organization, he will see all the records from all organizations.
 
 In this mode there are limitations with access permission check. In this mode works only System access level. If 
 some permission for some entity  will have access level less than System (Organization, Division, Business Unit, etc), 
 for this permission access will be disabled (same as None Access Level).
 
 When user switch to the System Access organization, new filter with organizations adds to the grids. So, user can filter
 data with available organizations.
 
 If user works in regular organization, search data limits by current organization but in `System Access Organization`
 this limit does not work, so user can search by all the data from the system. In search result items in this mode,
 there is additional information with record organization. 
 
 User can set only one organization as System Access Organization.
 
 In System Access Organization, user able to create and edit records. 
 
 Then the user creates a record in the `System Access Organization` he should specify the organization he's creating 
 the record for. This resolved with a two-step dialogue where on the first step the user selects an organization and 
 then proceeds to the entity creation form (that might be organization-specific).
 
 In this Organization selection form user can select any active organization from the system. 
 `System Access Organization` can be used as record organization as well as regular organization.
 
 During create or edit process, selected organization shown as label to inform user about selected organization.
 Additionally, all the data on this forms are filtered with selected organization.
 
 On view pages user can add additional activities and another related records. This records will be created in the 
 same organization, as the parent record.
 