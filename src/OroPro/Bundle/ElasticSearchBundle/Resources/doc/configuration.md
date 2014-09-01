Configuration
=============

`OroProElasticSearchBundle` provides useful possibilities of search engine configure for your needs.

Parameters
----------

At your disposal has next specific for `Elastic Search` parameters (in `app/parameters.yml`):
* **search_engine_host** - host name, which `Elastic Search` should be connected for;
* **search_engine_port** - port number, which `Elastic Search` should use for connection;
* **search_engine_username** - login for HTTP Auth authentication;
* **search_engine_password** - password for HTTP Auth authentication;
* **search_engine_auth_type** - HTTP Auth authentication type, different types allowed appropriate options
(for more information see [Elastic Search documentation](http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html#CO1-1)).


Configure connection
--------------------

For configure your `Elastic Search` engine you should put configuration to the `app\config.yml` under the `oro_search`.
Let's see example below, on this configuration we are took all parameters from `app/parameters.yml`:

```yml
 # ... other configuration
oro_search:
    search_engine_name: "%search_engine_name%"
    connection:
        search_engine_host:      "%search_engine_host%"
        search_engine_port:      "%search_engine_port%"
        search_engine_username:  "%search_engine_username%"
        search_engine_password:  "%search_engine_password%"
        search_engine_auth_type: "%search_engine_auth_type%"
 # ... other configuration
...
```

This configuration will be compiled in the `Elastic Search` format and will be available from container in the next format:

```yml
oro_search:
    engine_parameters:
        connection:
            hosts: ['localhost:port']
            connectionParams:
                auth: ['username', 'password', 'auth_type']
        # ... other specific configuration
```

You can don't add configuration to the `app\config.yml` file so as not to clutter up it.
In this case `OroProElasticSearchBundle` will take all described parameter from `app/parameters.yml` automatically and your
configuration file will content only next configuration:

```yml
 # ... other configuration
oro_search:
    search_engine_name: "%search_engine_name%"
 # ... other configuration
...
```

Also, possible situation, when you described your configuration in `app\config.yml` without using parameters.
In this case wins parameters if appropriate parameter is not empty. In terms of `Elastic Search` parameters
`host` and `port` pass in one group and parameters `username`, `password` and `auth_type` in other group. Based on this
you can have next situation, when you have configuration like this:
```yml
 # ... other configuration
oro_search:
    search_engine_name: "%search_engine_name%"
    connection:
        search_engine_host:      customHost
        search_engine_port:      9200
        search_engine_username:  admin
        search_engine_password:  admin
        search_engine_auth_type: basic
 # ... other configuration
...
```
 * `host` defined as null in parameters (`search_engine_host: ~`), then values will come from global config (`hosts: ['customHost:9200']`);
 * `host` defined in parameters (`search_engine_host: localhost`), then values will come from parameters (`hosts: ['localhost']`).
    **Note:** port will come from parameters too;
 * `username`, `password` and `auth_type` defined as null in parameters, then values will come from global config;
 * **any** of `username`, `password` or `auth_type` defined as not null in parameters, then values will come from parameters;

Configure index
---------------

All configuration, which needs to create index for `Elastic Search` contains in `search.yml` files and in main `config.yml`.
More information you will find in [documentation](https://github.com/laboro/platform/blob/master/src/Oro/Bundle/SearchBundle/Resources/doc/configuration.md#entity-configuration) for `OroSearchBundle`.
This configuration will be compiled in the `Elastic Search` format and will be available from container in the next format:

```yml
oro_search:
    engine_parameters:
        connection:
            # ... connection configuration
        index:
            index: indexName                          # name of index
                body:
                    mappings:                         # mapping parameters
                        <entityTypeName-1>:           # name of type
                            properties:
                                <entityField-1>:      # name of field
                                    type:   string    # type of field
                                # ... list of entity fields
                                <entityField-N>:
                                    type:   string
                        # ... list of types
                        <entityTypeName-N>:
                            properties:
                                <entityField-1>:
                                    type:   string
```
