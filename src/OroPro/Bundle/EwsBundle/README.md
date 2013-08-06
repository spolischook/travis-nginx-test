EwsBundle
==============

This bundle provides a functionality to work with Microsoft Exchange Web Services.

Installation
------------

### Step 1) Get the bundle and the library

Add on composer.json (see http://getcomposer.org/)

    "require" :  {
        // ...
        "oro/ews-bundle": "dev-master",
    }

### Step 2) Register the bundle

To start using the bundle, register it in your Kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Oro\Bundle\EwsBundle\OroEwsBundle(),
    );
    // ...
}
```

Usage
-----

``` php
<?php
    // Accessing EWS connector
    /** @var $ews \OroPro\Bundle\EwsBundle\Connector\EwsConnector */
    $ews = $this->get('oro_ews.connector');

    // Accessing the search query builder
    /** @var $queryBuilder \OroPro\Bundle\EwsBundle\Connector\SearchQueryBuilder */
    $queryBuilder = $this->get('oro_ews.search_query.builder');

    // Building a search query
    $query = $queryBuilder
        ->from('test@test.com')
        ->subject('notification')
        ->get();

    // Request an Exchange Server for find emails
    $emails = $ewsConnector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX, null, $query);
```
