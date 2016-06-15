Request builders
================

Request builder is a separate class used to build some specific part of search request to ElasticSearch based on
source Query object. Request builder must implement interface
_\OroPro\Bundle\ElasticSearchBundle\RequestBuilder\RequestBuilderInterface_ - according to it builder receives
Query object and existing request array, modifies it and returns altered request array.

There are four default request builders.


### FromRequestBuilder

**Class:** OroPro\Bundle\ElasticSearchBundle\RequestBuilder\FromRequestBuilder

Builder gets "from" part of a query and if it has specific entities converts them to required
[index types](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-search.html).


### WhereRequestBuilder

**Class:** OroPro\Bundle\ElasticSearchBundle\RequestBuilder\WhereRequestBuilder

Builder iterates over all "where" part conditions and passed them to chain of part builders
used to process specific condition operators.

- **ContainsWherePartBuilder** - processes operators "contains" (~) and "not contains" (!~),
adds
[match query](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-match-query.html)
for "all_text" field with nGram tokenizer or
[wildcard query](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html)
for regular fields;
- **EqualsWherePartBuilder** - processes operators "equals" (=) and "not equals" (!=), adds
[match query](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-match-query.html);
- **RangeWherePartBuilder** - processes arithmetical operators applied
to numeric values - > (gt), >= (gte), < (lt) and <= (lte), adds appropriate
[range query](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-range-query.html);
- **InWherePartBuilder** - processes operators "in" (in) and "not in" (!in), converts set to several "equals" or
"not equals" conditions that uses
[match query](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-match-query.html).

Each part builder receives field name, field type, condition operator, value, boolean keyword and source request, and
similar to request builder returns altered request.


### OrderRequestBuilder

**Class:** OroPro\Bundle\ElasticSearchBundle\RequestBuilder\OrderRequestBuilder

Builder gets order field and order direction from query and if they are defined converts them to
[sort part] (http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-sort.html)
of a search request. Result is sorted by relevancy by default.


### LimitRequestBuilder

**Class:** OroPro\Bundle\ElasticSearchBundle\RequestBuilder\LimitRequestBuilder

Builder gets first result and max results values from query and if they defined converts them to
[from and size parts](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-from-size.html)
of a search request.
