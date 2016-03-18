Debug commands
--------------

### oro:api:dump
This command shows all resources available through Data API.

Run this command without parameters to see all available resources:

```bash
php app/console oro:api:dump
```

or specify the `request-type` option if you need to know resources for a particular request type:

```bash
php app/console oro:api:dump --request-type=rest --request-type=json_api
```

### oro:api:debug
This command shows details about registered Data API actions and processors.

If you want to know all actions run this command without parameters:

```bash
php app/console oro:api:debug
```

If you want to know which processors are registered for a particular action run this command with the action name as an argument:

```bash
php app/console oro:api:debug get_list
```

The `request-type` option can be used to see the processors which will be executed for a particular request type:

```bash
php app/console oro:api:debug get_list --request-type=rest --request-type=json_api
```

### oro:api:config:dump
This command shows configuration for a particular entity.

Run this command and specify entity class or entity alias as an argument:

```bash
php app/console oro:api:config:dump "Oro\Bundle\UserBundle\Entity\User"
```

or

```bash
php app/console oro:api:config:dump users
```

To see the configuration for a particular request type you can use the `request-type` option:

```bash
php app/console oro:api:config:dump users --request-type=rest --request-type=json_api
```

The `section` option can be used to see a configuration of an entity when it is referenced by another entity:

```bash
php app/console oro:api:config:dump addresses --section=relations
```

By default no extra configuration data are added into output, but they can be added with the `--extra` option.
The value for `extra` option can be: filters, sorters, virtual_fields, descriptions or FQCN of a ConfigExtraSectionInterface or ConfigExtraInterface, e.g.

to show `sorters`

```bash
php app/console oro:api:config:dump users --extra=sorters
```

to see human-readable representation of an entity and its fields

```bash
php app/console oro:api:config:dump users --extra=descriptions
```

or if a new extra section was added just pass the FQCN of a ConfigExtra

```bash
php app/console oro:api:config:dump users --extra="Acme\Bundle\AcmeBundle\Config\AcmeConfigExtra"
```

or it's also possible to pass multiple options

```bash
php app/console oro:api:config:dump users --extra=sorters --extra=descriptions --extra=filters --extra="Acme\Bundle\AcmeBundle\Config\AcmeConfigExtra"
```


### oro:api:metadata:dump
This command shows metadata for a particular entity.

To see metadata run this command and specify entity class or entity alias as an argument:

```bash
php app/console oro:api:metadata:dump "Oro\Bundle\UserBundle\Entity\User"
```

or

```bash
php app/console oro:api:metadata:dump users
```

If you want to see entity metadata that is used for a particular request type you can use the `request-type` option:

```bash
php app/console oro:api:metadata:dump users --request-type=rest --request-type=json_api
```

### oro:api:config:dump-reference
This command shows the structure of `Resources/config/oro/api.yml`.

```bash
php app/console oro:api:config:dump-reference
```
