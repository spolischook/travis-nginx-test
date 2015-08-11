# OroCRMProLDAPBundle

## Function

Adds **LDAP Integration** which enables user synchronization with LDAP server.
Additionally enables login using LDAP credentials for users who have been
synchronized to/from LDAP.

## Configuration

Create LDAP Integration under *System > Integrations > Manage Integrations*.

### Options

#### Basic Information

 - **Hostname:** Address of LDAP Server
 - **Port:** Port of LDAP Server
 - **Encryption:** Encryption used for communication with server
    (SSL/TLS/None).
 - **Base Distinguished Name:** The default base DN used for searching (e.g.,
    for accounts). This option is required for most account related operations
    and should indicate the DN under which accounts are located.
 - **Username:** The default credentials username. Some servers require that
    this be in DN form. This must be given in DN form if the LDAP server
    requires a DN to bind and binding should be possible with simple usernames.
 - **Password:** The default credentials password (used only with username
    above).
 - **Account Domain Name:** The FQDN domain for which the target LDAP server is
    an authority (e.g., example.com).
 - **Short Account Domain Name:** The ‘short’ domain for which the target LDAP
    server is an authority. This is usually used to specify the NetBIOS domain
    name for Windows networks but may also be used by non-AD servers.
 - **Connection Check Button:** Checks if provided credentials are valid and it
    is possible to reach and bind to server. This does NOT check if mapping
    settings are correct.
 - **Default Owner:** Imported users will be under same organization as this
    default owner user.

#### Synchronization settings

 - **Enable Two Way Sync:** Exports all modified users to ldap server.
    Will overwrite if **Sync Priority** is set to Local wins.
 - **Sync Priority:** If set to remote wins. Imported data will always overwrite
    data present in CRM. This is because LDAP does not provide updated at
    timestamps for comparison. If set to Local wins data from LDAP will not
    overwrite CRM in case of conflict, but will still import if such user does
    not exist and create a new one.

#### Mapping settings

Setting specifying how users are mapped to LDAP records.

 - **User Filter:** Filter used for searching for users in LDAP.
 - **List of User Attributes:** A list of user entity fields which can be mapped
    to ldap records. These are determined by checking import/export excluded
    config value on User entity. Each has an empty field which can be filled by
    attribute name in LDAP. Username field has to be filled and is used as
    identity for Users. This can be also configured.
 - **Role Filter:** Filter used for searching for role records in LDAP.
 - **Role Id Attribute:** Attribute on role record which is used as role
    identifier.
 - **Role User Id Attribute:** Attribute storing list of users who have assigned
    that role.
 - **Role Mapping:** It is possible to map multiple CRM roles to one role record
    in LDAP. **LDAP Role Name** represents id of that role in LDAP and **CRM
    Role Names** is a list of roles in CRM which will be assigned to qualified
    users.
 - **Export User Object Class:** ObjectClass attribute of ldap record created
    when users are exported. This is only used if user was not yet synchronized.
    If user was synchronized, stored DN from that sync is used.
 - **Export User Base Distinguished Name:** DN Under which exported users
    without already created DN will be stored.

## Implementation

Implementation follows documentation of
[OroIntegrationBundle](//github.com/laboro/platform/tree/master/src/Oro/Bundle/IntegrationBundle)
and
[OroImportExportBundle](//github.com/laboro/platform/tree/master/src/Oro/Bundle/ImportExportBundle).

Communication with LDAP is implemented using
[Zend LDAP (v2.0.*)](//framework.zend.com/manual/2.0/en/modules/zend.ldap.introduction.html).
