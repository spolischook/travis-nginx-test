CHANGELOG for 1.10.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.10.0 versions.
* 1.10.0 (2015-08-26)
 * LDAP integration
 * Fixed issues with Outlook add-in download
 * Fixed issues with connecting Outlook to OroCRM without access to system configuration, meaning that any user role may now use this integration
 * Fixed issue with Outlook plugin sync before start of a workday

CHANGELOG for 1.9.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.9.0 versions.
* 1.9.0 (2015-04-28)
 * Configuration on the organization level
 * Synchronization of custom attributes of Magento entities

CHANGELOG for 1.8.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.8.0 versions.
* 1.8.0 (2015-01-19)
 * Global access to data from multiple organizations.
This feature provides the ability to access all existing data across all organizations through a specially designated "System access" organization. While in this organization, users will be able to view, edit, and delete any entity record from any organization, as well as create new ones.
To designate an organization with the system access, go to System → User management → Organizations, and check the respective checkbox on the Edit form of desired organization. Only one System access organization is allowed.
Please note that simply assigning users to the System access organization will not necessarily grant them access to all entity data. In order to access and manage entity records in the System access organization users have to have System level permissions on the respective actions in their role. If their permissions is lower than System (e.g. Organization, or Business Unit), they will be able to switch to the System access organization but won't have the access to entity records.
 * Optimized user login experience in multi-organizational environment.
The user login UI has been reorganized in order to provide more clean user experience. The organization selector has been removed, and the user is now logged in to the last used organization, which is remembered across sessions. If the organization is not available for some reason, or in case of the very first login, the user is logged in to the first organization available to him, and a prompt to use organization selector is displayed.

CHANGELOG for 1.7.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.7.0 versions.
* 1.7.0 (2014-12-18)
 * Entity management on the organization level.
Custom entities and entity fields now have organization scope, meaning that they can be created and used not only system-wide, but in a limited set of organizations.
From the user standpoint this means that it is possible to have one and the same entity with a very different attribute set in two different organizations. For example, if two organizations exist in the system, they both may have entities names Customers and Orders, but if the first organization is retail, and the second one is a wholesale company, their attribute sets of these entities could be very different.
It is also possible to enable an entity in scope of some organization and completely disable its use for another.
 * Other issues.
An API for integration of Tasks with MS Outlook has been added.

CHANGELOG for 1.6.2
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.6.2 versions.
* 1.6.2 (2014-12-02)
 * List of improvements and fixed bugs
 <ul><li>Plaform and CRM 1.4.2 compatible changes</li></ul>

CHANGELOG for 1.6.1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.6.1 versions.
* 1.6.1 (2014-11-17)
 * List of improvements and fixed bugs
 <ul><li>Made contact address, email and phone entities extendable</li>
 <li>Fixed missing Campaign details grid and chart</li>
 <li>Fixed sales funnel chart view</li></ul>

CHANGELOG for 1.6.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.6.0 versions.

* 1.6.0 (2014-10-15)
 * <b>New features.</b>
The Enterprise Edition is built upon the simultaneously released 1.4.0 version of Community edition, so it will include all its new features and fixes. Below is the list of features exclusive to Enterprise version.
 * <b>Multiple Organizations.</b>
In Enterprise Edition it is now possible to create and maintain multiple organizations on a single installation of OroCRM. Multiple organizations are needed when the business of the CRM owner is complex and consists of two or more independent units. To justify the necessity of use of multiple organizations, the separation between the parts of the business must be deep, otherwise business units within a single organization will be sufficient. The good rule of thumb is that if a business unit has its own P&L, it should correspond to an individual organization in the OroCRM. Here are few examples of the cases where business units are not enough and organizations are needed:
<ul><li>Two organizations can be united under one business but be of very different nature—think the wholesale and retail department of some company. They have completely different clients, workflows, business rules, branding, everything—but they are still parts of a single business, and both these parts are managed by a single CEO.</li>
<li>A foreign branch of the business is another good candidate for separation into a different organization. An overseas branch will definitely have a different set of clients, a slightly different workflow and a set of organizational rules, it will have different base currency and locale, etc.</li></ul>
Organizations may be created and managed by admins under the System > User management > Organizations menu. There is no limit on the number of organizations for an installation and it is dictated entirely by the needs of the particular business.<br>
Users' access to many organizations is managed in the Access Settings section of their user pages. All organizations and their business units are combined in a single tree so you can easily evaluate and manage the access information.<br>
When multiple organizations are added to the system the login screen will feature an additional organization selector. The user cannot log in into an organization he does not have an access to. If the user has access to more then one organization he can choose to log in to either of them.<br>
After the login, the user who has an access to more then one organization will note an organization switcher (three vertical dots) in the top left corner of the screen. To switch organizations, simply click on the switcher and select an organization from the list. Note that if the user's access rights were changed while he's in session, a logout is required for these new rights to come into effect.<br>
Organizations share no data between them other than users and general entity schema. This means that if the same entity is utilized within multiple organizations, within every organization only the certain scope of its records will be accessible—but the workflow associated with this entity will be the same across all organizations.
 * <b>Integration with MS Outlook.</b>
A new Outlook Sync application (distributed separately) allows you to synchronize contacts between OroCRM and Outlook. Currently we supported integration with Outlook 2010 and 2012 versions only.
After you download and install the application you should log into it using the following credentials:
<ul><li>URL of the OroCRM instance</li>
<li>Your username</li>
<li>API key that can be obtained on your My User page in the OroCRM. The API key resembles the password so keep it safe!</li></ul>
The integration is enabled by default in OroCRM, but you may choose to disable it when necessary in System Configuration menu. You can also use this menu to finely configure the synchronization settings such as synchronization frequency or conflict resolution method.
 * <b>ElasticSearch support.</b>
It is now possible to utilize ElasticSearch engine for the indexed search within OroCRM. This engine greatly increases search performance and capabilities that will be especially noticeable if you work with big amounts of data.

CHANGELOG for 1.6.0-RC1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.6.0-RC1 versions.

* 1.6.0 (2014-09-30)
 * <b>New features.</b>
The Enterprise Edition is built upon the simultaneously released 1.4.0 version of Community edition, so it will include all its new features and fixes. Below is the list of features exclusive to Enterprise version.
 * <b>Multiple Organizations.</b>
In Enterprise Edition it is now possible to create and maintain multiple organizations on a single installation of OroCRM. Multiple organizations are needed when the business of the CRM owner is complex and consists of two or more independent units. To justify the necessity of use of multiple organizations, the separation between the parts of the business must be deep, otherwise business units within a single organization will be sufficient. The good rule of thumb is that if a business unit has its own P&L, it should correspond to an individual organization in the OroCRM. Here are few examples of the cases where business units are not enough and organizations are needed:
<ul><li>Two organizations can be united under one business but be of very different nature—think the wholesale and retail department of some company. They have completely different clients, workflows, business rules, branding, everything—but they are still parts of a single business, and both these parts are managed by a single CEO.</li>
<li>A foreign branch of the business is another good candidate for separation into a different organization. An overseas branch will definitely have a different set of clients, a slightly different workflow and a set of organizational rules, it will have different base currency and locale, etc.</li></ul>
Organizations may be created and managed by admins under the System > User management > Organizations menu. There is no limit on the number of organizations for an installation and it is dictated entirely by the needs of the particular business.<br>
Users' access to many organizations is managed in the Access Settings section of their user pages. All organizations and their business units are combined in a single tree so you can easily evaluate and manage the access information.<br>
When multiple organizations are added to the system the login screen will feature an additional organization selector. The user cannot log in into an organization he does not have an access to. If the user has access to more then one organization he can choose to log in to either of them.<br>
After the login, the user who has an access to more then one organization will note an organization switcher (three vertical dots) in the top left corner of the screen. To switch organizations, simply click on the switcher and select an organization from the list. Note that if the user's access rights were changed while he's in session, a logout is required for these new rights to come into effect.<br>
Organizations share no data between them other than users and general entity schema. This means that if the same entity is utilized within multiple organizations, within every organization only the certain scope of its records will be accessible—but the workflow associated with this entity will be the same across all organizations.
 * <b>Integration with MS Outlook.</b>
A new Outlook Sync application (distributed separately) allows you to synchronize contacts between OroCRM and Outlook. Currently we supported integration with Outlook 2010 and 2012 versions only.
After you download and install the application you should log into it using the following credentials:
<ul><li>URL of the OroCRM instance</li>
<li>Your username</li>
<li>API key that can be obtained on your My User page in the OroCRM. The API key resembles the password so keep it safe!</li></ul>
The integration is enabled by default in OroCRM, but you may choose to disable it when necessary in System Configuration menu. You can also use this menu to finely configure the synchronization settings such as synchronization frequency or conflict resolution method.
 * <b>ElasticSearch support.</b>
It is now possible to utilize ElasticSearch engine for the indexed search within OroCRM. This engine greatly increases search performance and capabilities that will be especially noticeable if you work with big amounts of data.

CHANGELOG for 1.5.1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.5.1 versions.

* 1.5.1 (2014-09-22)
 * Stored XSS Vulnerability fixes
    * added "|json_encode|raw" for values outputted in JS objects
    * removed "|raw" from outputs of path in url attributes
    * added "e('html_attr')|raw" when outputting html attributes
    * removed mentions of "flexible entity" and unused code
    * added validator for css field of embedded form, now if user will enter html tags in this field he will get an error message
    * added stiptags filter for css of embedded forms
    * changed translation message oro.entity_config.records_count.label to contain placeholder of records count and use UI.link macros in template instead of slicing str
    * changed method of validation of emails on the client, old validation was working very slowly with some values like '">< img src=d onerror=confirm(/provensec/);>', n
    * removed "trans|raw" where it's not required
    * minor changes in templates to improve readability
    * added Email validator for Lead
    * fixed XSS vulnerability in Leads, Case Comments, Notes, Embedded forms, Emails, Business Units, Breadcrumbs
    * fixed escaping of page title

CHANGELOG for 1.5.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.5.0 enterprise versions.

* 1.5.0 (2014-08-14)
 * PostgreSQL support

CHANGELOG for 1.4.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.4.0 enterprise versions.

* 1.4.0 (2014-07-25)
 * Compatibility with 1.3.0 Platform

CHANGELOG for 1.3.1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.3.1 enterprise versions.

* 1.3.1 (2014-05-12)
 * Compatibility with 1.2.0 RC1 OroCRM

CHANGELOG for 1.3.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.3.0 enterprise versions.

* 1.3.0 (2014-04-28)
 * Advanced chart engine
 * Microsoft Exchange integration

