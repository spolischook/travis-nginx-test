EwsBundle
==============

This bundle provides a functionality to work with Microsoft Exchange Web Services.

Usage
-----

``` php
<?php
    // Accessing EWS connector
    /** @var $ews OroPro\Bundle\EwsBundle\Connector\EwsConnector */
    $ews = $this->get('oro_pro_ews.connector');

    // Accessing the search query builder
    /** @var $queryBuilder OroPro\Bundle\EwsBundle\Connector\SearchQueryBuilder */
    $queryBuilder = $this->get('oro_pro_ews.search_query.builder');

    // Building a search query
    $query = $queryBuilder
        ->from('test@test.com')
        ->subject('notification')
        ->get();

    // Request an Exchange Server for find emails
    $emails = $ewsConnector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX, $query);
```

Synchronization with EWS
------------------------
At first you need to configure EWS connection on System > Configuration > Integrations page. There you need to enter server name, domain(s) and admin user credentials.
Admin user account used for connecting to Exchange server should be able to impersonate as any mailbox user involved in synchronization with OroCRM.
To configure Microsoft Exchange Impersonation for specific users or groups of users, please see [Configuring Exchange Impersonation](http://msdn.microsoft.com/en-us/library/office/bb204095\(v=exchg.140\).aspx).

By default the synchronization is executed by CRON every minute. Also you can execute it manually using the following command:
```bash
php app/console oro:cron:ews-sync
```

Email synchronization functionality is implemented in the following classes:

 - EwsEmailSynchronizer - extends OroEmailBundle\Sync\AbstractEmailSynchronizer class to work with EWS mailboxes.
 - EwsEmailSynchronizationProcessor - implements email synchronization algorithm used for synchronize emails through EWS.
 - EmailSyncCommand - allows to execute email synchronization as CRON job or through command line.
