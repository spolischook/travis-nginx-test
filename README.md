OroCRM Professional
========================

Welcome to OroCRM Professional an paid modules for base version of OroCRM - Open Source Client Relationship Management (CRM) tool.

This document contains information on how to download, install, and start
using OroCRM. For a more detailed explanation, see the [Installation]
chapter.

Requirements
------------

OroCRM Professional requires OroCRM, Oro Platform Professional, Symfony 2, Doctrine 2 and PHP 5.3.3 or above.

Installation
------------

```bash
git clone https://github.com/orocrm/crm-professional.git

curl -s https://getcomposer.org/installer | php

php composer.phar install
```

Run unit tests
--------------

To run unit tests of any bundnles :

```bash
phpunit
```

Use as dependency in composer
-----------------------------
Until it's a private repository and it's not published on packagist :

```yaml
    "require": {
        "oro/crm": "dev-master",
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/orocrm/crm-professional.git",
            "branch": "master"
        }
    ],
```
