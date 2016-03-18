Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration structure](#configuration-structure)
 - ["exclusions" configuration section & "exclude" flag](#exclusions-configuration-section--exclude-flag)
 - ["entities" configuration section](#entities-configuration-section)
 - ["relations" configuration section](#relations-configuration-section)

Overview
--------

The configuration declares all aspects related to specific entity. The  configuration should be placed in `Resources/config/oro/api.yml` to be automatically loaded.

All entities, except custom entities, dictionaries and enumerations are not accessible through Data API. To allow usage of an entity in Data API you have to enable it directly. For example, to make `Acme\Bundle\ProductBundle\Product` entity available through Data API you can write the following configuration:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

Configuration structure
-----------------------

To get the overall configuration structure, execute the following command:

```bash
php app/console oro:api:config:dump-reference
```

By default this command shows configuration of nesting entities. To simplify the output you can use the `--max-nesting-level` option, e.g.

```bash
php app/console oro:api:config:dump-reference --max-nesting-level=0
```

The default nesting level is `3`. It is specified in [services.yml](../config/services.yml) via the `oro_api.config.max_nesting_level` parameter. So, if needed, you can easily change this value.

```yaml
parameters:
    # the maximum number of nesting target entities that can be specified in 'Resources/config/oro/api.yml'
    oro_api.config.max_nesting_level: 3
```

The first level sections of configuration are:

* [exclusions](#exclusions-configuration-section--exclude-flag) - describes entities and fields that should be excluded from Data API. This can be useful for example to exclude security specific data from being accessible via Data API.
* [entities](#entities-configuration-section)   - describes the configuration of entities.
* [relations](#relations-configuration-section)  - describes the configuration of relationships.

Top level configuration example:

```yaml
oro_api:
    exclusions:
        ...
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            filters:
                fields:
                    ...
            sorters:
                fields:
                    ...
            exclude: ~
        ...
    relations:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            filters:
                fields:
                    ...
            sorters:
                fields:
                    ...
        ...
```

"exclusions" configuration section & "exclude" flag
---------------------------------------------------

The `exclusions` configuration section describes whether whole entity or some of its fields should be excluded from Data API.

Each item has next properties:

* **entity** *string* The fully-Qualified Class Name of an entity.
* **field** *string* The name of a field. This is optional property.

Example:

```yaml
oro_api:
    exclusions:
        # whole entity exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1 }
        # exclude field1 of Acme\Bundle\AcmeBundle\Entity\Entity2 entity
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity2, field: field1 }
```

The same behavior can be reached using the `exclude` property under the  configuration of an entity, e.g.

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            exclude: true
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity2:
            fields:
                field1:
                    exclude: true
```

Also the `exclude` property can be used to indicate whether filtering or sorting for certain field should be disabled. Please note that filtering and sorting for the excluded field are disabled automatically, so it's not possible to filter or sort by excluded field.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            sorter:
                fields:
                    field1:
                        exclude: true
            filters:
                fields:
                    field1:
                        exclude: true
```

Please note that `oro_api.exclusions` rules are applicable only for Data API. In case if an entity or its' field(s) should be excluded globally use `Resources/config/oro/entity.yml`, e.g.:

```yaml
oro_entity:
    exclusions:
        # whole entity exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1 }
        # exclude field1 of Acme\Bundle\AcmeBundle\Entity\Entity2 entity
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity2, field: field1 }
```

"entities" configuration section
--------------------------------

The `entities` section describes a configuration of entities.

Each entity can have next properties:

* **label** *string* A human-readable representation of the entity. Used in auto generated documentation only.
* **plural_label** *string* A human-readable representation in plural of the entity. Used in auto generated documentation only.
* **description** *string* A human-readable description of the entity. Used in auto generated documentation only.
* **inherit** *boolean* By default `true`. The flag indicates that the configuration for certain entity should be merged with the configuration of a parent entity. If a derived entity should have completely different configuration and merging with parent configuration is not needed the flag should be set to `false`.
* **exclusion_policy** *string* - Can be `all` or `none`. By default `none`. Indicates the exclusion strategy that should be used for the entity. `all` means that all fields are not configured explicitly will be excluded. `none` means that only fields marked with `exclude` flag will be excluded.
* **disable_partial_load** *boolean* The flag indicates whether usage of Doctrine partial objects is disabled. By default `false`. It can be helpful for entities with table inheritance mapping.
* **max_results** *integer* The maximum number of entities in the result. Set -1 (it means unlimited), zero or positive value to set the limit. Can be used to set the limit for both root and related entities.
* **order_by** *array* The property can be used to configure default ordering. The item key is the name of a field. The value can be `ASC` or `DESC`.
* **hints** *array* Sets [Doctrine query hints](http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-hints). Each item can be a string or an array with `name` and `value` keys. The string value is a short form of `[name: hint name]`.
* **post_serialize** *callable* A handler to be used to modify serialized data.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            label:                "Acme Entity"
            plural_label:         "Acme Entities"
            description:          "Acme Entities description"
            inherit:              false
            exclusion_policy:     all
            disable_partial_load: false
            max_results:          25
            order_by:
                field1: DESC
                field2: ASC
            hints:
                - HINT_TRANSLATABLE
                - { name: HINT_FILTER_BY_CURRENT_USER }
                - { name: HINT_CUSTOM_OUTPUT_WALKER, value: "Acme\Bundle\AcmeBundle\AST_Walker_Class"}
            post_serialize: ["Acme\Bundle\AcmeBundle\Serializer\MySerializationHandler", "serialize"]
            excluded:             false
            fields:
                ...
            filters:
                ...
            sorters:
                ...
```

* **fields** - This section describes entity fields' configuration.

Each field can have next properties:

* **label** *string* A human-readable representation of the field. Used in auto generated documentation only.
* **description** *string* A human-readable description of the field. Used in auto generated documentation only.
* **property_path** *string* The property path to reach the fields' value. Can be used to rename a field or to access to a field of related entity.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields:
                # the "firstName" field will be renamed to the "name" field
                name:
                    property_path: firstName

                # the "addressName" field will contain the value of the "name" field of the "address" related entity
                addressName:
                    property_path: address.name

```

* **data_transformer** - The data transformer(s) to be applies to the field value. Can be specified as service name, array of service names or as FQCN and method name.

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields
                field1:
                    data_transformer: "my.data.transformer.service.id"
                field2:
                    data_transformer:
                        - "my.data.transformer.service.id"
                        - ["Acme\Bundle\AcmeBundle\DataTransformer\MyDataTransformer", "transform"]
```

* **collapse** *boolean* Indicates whether the entity should be collapsed. It is applicable for associations only. It means that target entity should be returned as a value, instead of an array with values of entity fields. Usually this property is set by [get_relation_config](./actions.md#get_relation_config-action) processors to get identifier of the related entity.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields:
                field1: # full syntax for "collapse" property
                    collapse:         true
                    exclusion_policy: all
                    fields:
                        targetField1: null
                field2: # short syntax for "collapse" property
                    fields: targetField1
```

* **exclude** *boolean* Indicates whether the field should be excluded. This property is described above in ["exclusions" configuration section & "exclude" flag](#exclusions-configuration-section--exclude-flag).

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                field1:
                    label:            "Acme name"
                    description:      "Acme name description"
                    property_path:    "firstName"
                field2:
                    exclude: true
```

* **filters** - This section describes fields by which the result data can be filtered. It contains two properties: `exclusion_policy` and `fields`.
    * **exclusion_policy** *string* Can be `all` or `none`. By default `none`. Indicates the exclusion strategy that should be used. `all` means that all fields are not configured explicitly will be excluded. `none` means that only fields marked with `exclude` flag will be excluded.
    * **fields** This section describes a configuration of each field that can be used to filter the result data. Each filter can have the following properties:
        * **description** *string* A human-readable description of the filter. Used in auto generated documentation only.
        * **exclude** *boolean* Indicates whether filtering by this field should be disabled. By default `false`.
        * **property_path** *string* The property path to reach the fields' value. The same way as above in `fields` configuration section.
        * **data_type** *string* The data type of the filter value. Can be `boolean`, `integer`, `string`, etc.
        * **allow_array** *boolean* A flag indicates whether the filter can contains several values. By default `false`.
        * **default_value** - The default value for the filter.

The example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            filters:
                exclusion_policy: all
                fields:
                    field1:
                        data_type: integer
                        exclude: true
                    field2:
                        data_type: string
                        property_path: firstName
                        description: "My filter description"
                    field3:
                        data_type: boolean
                        allow_array: false
                        default_value: true
```

* **sorters** - This section describes fields by which the result data can be sorted. It contains two properties: `exclusion_policy` and `fields`.
    * **exclusion_policy** *string* Can be `all` or `none`. By default `none`. Indicates the exclusion strategy that should be used. `all` means that all fields are not configured explicitly will be excluded. `none` means that only fields marked with `exclude` flag will be excluded.
    * **fields** - This section describes a configuration of each field that can be used to sort the result data. Each sorter can have the following properties:
        * **exclude** *boolean* Indicates whether sorting by this field should be disabled. By default `false`.
        * **property_path** *string* The property path to reach the fields' value. The same way as above in `fields` configuration section.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            sorters:
                fields:
                    field1:
                        property_path: firstName
                    field2:
                        exclude: true
```

"relations" configuration section
---------------------------------

The `relations` configuration section describes a configuration of an entity if it is used in a relationship. This section is absolutely identical to the [entities](#entities-configuration-section) section, the only difference is the `exclude` flag for an entity - it's not available under this configuration section.


Please refer to [actions](./actions.md#context-class) documentation section for more detail about **how to use configuration** in Data API logic.
