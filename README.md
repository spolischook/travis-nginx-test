# OroCRMProDemoDataBundle
Package that provides demo data that covers different business scenarios.

Table of content
-----------------

- [Requirements](#requirements)
- [Use as dependency in composer](#use-as-dependency-in-composer)
- [Usage](#usage)

Requirements
------------

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
        "orocrmpro/demo-data-bundle": "dev-master"
    }
```

Usage
-----------------------------

Loading demo data fixtures for different scenarios(b2c,b2b,multi):


```bash
app/console oro:migration:live:data:load --fixtures-type=b2c
```

