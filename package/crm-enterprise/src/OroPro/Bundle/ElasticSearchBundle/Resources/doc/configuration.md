Configuration
=============

OroProElasticSearchBundle provides ability to configure search engine for your needs.

Parameters
----------

ElasticSearch uses following parameters from file `app/parameters.yml`:

* **search_engine_name** - engine name, must be "elastic_search" for ElasticSearch engine;
* **search_engine_host** - host name which ElasticSearch should be connected to;
* **search_engine_port** - port number which ElasticSearch should use for connection;
* **search_engine_username** - login for HTTP Auth authentication;
* **search_engine_password** - password for HTTP Auth authentication;
* **search_engine_auth_type** - HTTP Auth authentication type, different types allowed appropriate options
(for more information see [ElasticSearch documentation](http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html#_example_configuring_http_basic_auth)).

Basically if you have ElasticSearch server running that's all you have to define - search engine will automatically
define client and index configuration and then create index. But if you need to use more precise configuration
you can define it (see following chapters).


Client configuration
--------------------

To configure your ElasticSearch engine you should put configuration to the `app/config.yml` under the oro_search.
Configuration parameters from `app/parameters.yml` will be converted to the ElasticSearch format
and will be available in the following format:

```yml
oro_search:
    engine: %search_engine_name%
    engine_parameters:
        client:
            hosts: ['%search_engine_host%:%search_engine_host%']
            connectionParams:
                auth: ['%search_engine_username%', '%search_engine_password%', '%search_engine_auth_type%']
        # ... other specific configuration
```

You might not add configuration to the `app/config.yml`. In this case OroProElasticSearchBundle
will take all described parameter from `app/parameters.yml` automatically and your configuration file
will have only following configuration:

```yml
oro_search:
    engine: "%search_engine_name%"
```

Also possible situation when you described your configuration in `app/config.yml` without using parameters.
In this case not empty parameter wins. In terms of ElasticSearch parameters
"host" and "port" passes in one group and parameters "username", "password" and "auth_type"` in other group.
Based on them you can have following situation:

```yml
oro_search:
    engine: "elastic_search"
    engine_parameters
        client:
            hosts: ["customHost:9200"]
            connectionParams:
                auth: ["admin", "admin", "basic"]
```

 * if host defined as null in parameters then values will come from global config (`hosts: ["customHost:9200"]`);
 * if host defined in parameters then values will come from default parameters (`hosts: ["127.0.0.1"]`),
 port will come from parameters too;
 * if "username", "password" and "auth_type" defined as null in parameters then values will come from global config;
 * if any of "username", "password" and "auth_type" defined as not null in parameters, then values will
 come from parameters;

More information about client configuration you can find in
[ElasticSearch documentation](http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html).


Index configuration
-------------------

All configuration, which needs to create index for ElasticSearch contains in `search.yml` files and in main `config.yml`.
This configuration will be converted to ElasticSearch mappings format and will be available in the following format:

```yml
oro_search:
    engine_parameters:
        client:
            # ... client configuration
        index:
            index: indexName                          # name of index
            body:
                mappings:                               # mapping parameters
                    <entityTypeName-1>:                 # name of type
                        properties:
                            <entityField-1>:            # name of field
                                type:   string          # type of field
                            # ... list of entity fields
                            <entityField-N>:
                                type:   string
                    # ... list of types
                    <entityTypeName-N>:
                        properties:
                            <entityField-1>:
                                type:   string
```

More information about index configuration you can find in
[ElasticSearch documentation](http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_index_operations.html).
