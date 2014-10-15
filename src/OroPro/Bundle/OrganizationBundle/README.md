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

Forms that depends on CustomEntityType will be limited.

Custom fields are limited by `OrganizationExclusionProvider`, fields assigned into another organization will be ignored.

Building Datagrid
-----------------
As custom fields could be set up into particular organization they should be displayed per organization within the datagrid.

`DynamicFieldsExtension` extends `DynamicFieldsExtension` from `OroEntityBundle` and filters fields for concrete organization.

Controls access to some objects
-------------------------------
Report or Segment could be created using some custom entity. It makes sense to restrict access to them in case, the entity was moved into another organization.

It's done by `RequestReportParamConverter` that checks whether requested report or segment (rather the entity) is available for the current organization.
