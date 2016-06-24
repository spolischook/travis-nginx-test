# Creating new maintenance branch

* Add branch configuration to [configuration.yml](./tool/src/Oro/Cli/Command/Repository/configuration.yml)
```
branches:
    maintenance/crm-enterprise-1.11: # maintenance branch in dev repository
        application/crm-enterprise: '1.11'
        application/crm: '1.9'
        application/platform: '1.9'
        package/platform: '1.9'               # branch '1.9' from package/platform (git@github.com:laboro/platform.git) used
        package/platform-enterprise: '1.11'   # branch '1.11' from package/platform-enterprise (git@github.com:laboro/platform-enterprise.git) used
        package/crm: '1.9'                    # branch '1.9' from package/crm (git@github.com:laboro/crm.git) used
        package/crm-enterprise: '1.11'        # branch '1.11' from package/crm-enterprise (git@github.com:laboro/crm-enterprise.git) used
        package/dotmailer: '1.9'
        package/ldap: '1.11'
        package/mailchimp: '1.9'
        package/magento-abandoned-cart: '1.9'
        package/google-hangout: '1.9'
        package/serialized-fields: '1.9'
        package/demo-data: '1.11'
        package/zendesk: '1.9'
        package/magento-contact-us: '1.9'
```

* Create new maintenance branch form master
```
git checkout -b maintenance/crm-enterprise-1.11
```

# Creating new maintenance branch from previous source versions
* Add branch configuration to [configuration.yml](./tool/src/Oro/Cli/Command/Repository/configuration.yml)
```
branches:
    maintenance/crm-enterprise-1.11: # maintenance branch in dev repository
        application/crm-enterprise: '1.11'
        application/crm: '1.9'
        application/platform: '1.9'
        package/platform: '1.9'               # branch '1.9' from package/platform (git@github.com:laboro/platform.git) used
        package/platform-enterprise: '1.11'   # branch '1.11' from package/platform-enterprise (git@github.com:laboro/platform-enterprise.git) used
        package/crm: '1.9'                    # branch '1.9' from package/crm (git@github.com:laboro/crm.git) used
        package/crm-enterprise: '1.11'        # branch '1.11' from package/crm-enterprise (git@github.com:laboro/crm-enterprise.git) used
        package/dotmailer: '1.9'
        package/ldap: '1.11'
        package/mailchimp: '1.9'
        package/magento-abandoned-cart: '1.9'
        package/google-hangout: '1.9'
        package/serialized-fields: '1.9'
        package/demo-data: '1.11'
        package/zendesk: '1.9'
        package/magento-contact-us: '1.9'
```

* Create new maintenance branch form master
```
git checkout -b maintenance/crm-enterprise-1.11
```

* Reset changes to first repository commit
```
git reset --hard 17e0be67fedeea1d6a36c63e36bca900366589c5
```

* Copy tools to your branch
```
git checkout master -- .idea .gitignore .travis.sh .travis.yml travis.php.ini tool
```

* Update build scripts if necessary
* Commit changes
* Run branch command, it will import new subtree using branches from upstreams according to configuration
```
php tool/console repository:branch-sync --two-way --force --add-subtree
```

* Update composer.json (for application and packages) and add composer.lock to repository (applications only)
```
git checkout master -- application/crm-enterprise/phpunit.xml.dist
composer install --working-dir=application/crm-enterprise
git add -f application/crm-enterprise/composer.lock
```

## Required files changes are
* remove composer.lock from `application/crm-enterprise/.gitignore`
* replace specific packages versions listed in package directory in `application/crm-enterprise/composer.json` with `"oro/crm": "self.version"`
* package `package/crm/composer.json` should use same `"oro/platform": "self.version"` to point internal versions
* add new repository with package type to `application/crm-enterprise/composer.json`
```
  "repositories": [
    {
      "type": "path",
      "url": "../../package/*"
    }
  ]
```
