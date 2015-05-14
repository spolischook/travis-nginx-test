# OroCRMProDemoDataBundle
Package that provides demo data that covers different business scenarios.

Table of content
-----------------

- [Requirements](#requirements)
- [Disable email delivery](#disable-email-delivery)
- [Use as dependency in composer](#use-as-dependency-in-composer)
- [Usage](#usage)

Requirements
------------


Disable email delivery
-----------------
```yaml
# app/config/config.yml

swiftmailer:
    disable_delivery:  true
```

Use as dependency in composer
-----------------------------

```yaml
    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/laboro/OroCRMProDemoDataBundle"
        }
        ...
    ],
    ...
    "require": {
        "oro/crm-pro-demo-data-bundle": "dev-master"
    }
```

Usage
-----------------------------

Loading demo data fixtures for different scenarios(B2C,B2B,Multi):


```bash
app/console oro:migration:live:demo:data:load --force --fixtures-type=B2C
app/console oro:migration:live:demo:data:load --force --fixtures-type=B2B
app/console oro:migration:live:demo:data:load --force --fixtures-type=Multi
```

Clean data fixtures:

```bash
app/console oro:migration:live:demo:data:load --clean
```
