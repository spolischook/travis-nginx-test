Index agent and search engine
=============================

Index agent and search engine are two basic classes used to work with ElasticSearch index and perform fulltext search.


Index agent
-----------

**Class:** OroPro\Bundle\ElasticSearchBundle\Engine\IndexAgent

Index agent is used by search engine to get index name, initialize client and perform reindex operation.
Agent receives DI configuration of search engine and based on it defines index name and entity mapping. Then it adds
additional settings to tokenize text fields and merge all generated data with external configuration.

Entity mapping built based on search entity configuration defined in `search.yml` files, main configuration and
field type mappings. Field type mapping are injected through DI as parameter
_oropro\_elasticsearch.field\_type\_mapping_:

```yml
text:
    type: string
    store: true
    index: not_analyzed
decimal:
    type: double
    store: true
integer:
    type: integer
    store: true
datetime:
    type: date
    store: true
    format: "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd"
```

To make search faster field that contains all text information ("all_text") converted to lowercase and
split into tokens using nGram tokenizer, so this field has custom search and index analyzers that defined
in additional index settings.

This data is used to create and initialize client (instance of Elasticsearch\Client) and then return it to
search engine to perform fulltext search.

Also agent provides ability to recreate whole index or recreate only one type for specific entity.
Full recreation deletes existing index and creates new one with defined configuration.
Recreation for specific entity deletes only one mapping for one specific type.
Recreation is used by search engine to perform reindex operation.


Search engine
-------------

**Class:** OroPro\Bundle\ElasticSearchBundle\Engine\ElasticSearch

Search engine is the core of search - it implement SearchEngine interface and used by SearchBundle as main engine.
Search engine uses index agent as a proxy that works directly with search index f.e. to get index name or
recreate whole index or part of it.

To perform save and delete operations search engine uses [ElasticSearch bulk API](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-bulk.html).
Deletion performs as is, but save requires to delete existing entity first and only then save new entity - it used to
avoid storing of old values that are not overridden because of empty fields.

Reindex operations recreate either whole search index or only one type of it, and then triggers save operation for
all affected entities.

Search engine uses [request builders](./request_builders.md) to build ElasticSearch search request
based on source query. Each request builder in chain receives current request, modifies it and returns altered data.
New request builders can be added to engine through DI.
