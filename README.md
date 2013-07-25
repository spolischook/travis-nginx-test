The Oro Platform Professional - Business Application Platform Professional (BAPP)
======================================================

The platform is based on the Symfony 2 framework.

This repository contains paid bundles - additional to the base version of Oro Platform (BAP) which allows to easily create new custom business applications.

Requirements
------------

Oro Platform Professional requires Oro Platform, Symfony 2, Doctrine 2 and PHP 5.3.3 or above.


Installation
------------

```bash
git clone https://github.com/orocrm/platform-professional.git

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
        "oro/platform-professional": "dev-master",
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/orocrm/platform-professional.git",
            "branch": "master"
        }
    ],
```
