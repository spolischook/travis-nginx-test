<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

/**
 * Provides a list of match types use in SearchQuery class
 */
class SearchQueryMatch
{
    /** It is equal to PREFIX_MATCH for QUERY_STRING query type and SUBSTRING_MATCH for RESTRICTION query type */
    const DEFAULT_MATCH = 0;
    /**
     * This type of the match is supported for QUERY_STRING search query only.
     * Examples: 'win product' matches 'win95 product', 'windows production line',
     * 'windows new product' or 'product of win'.
     * */
    const PREFIX_MATCH = 1;
    /**
     * This type of the match is supported for QUERY_STRING search query only.
     * Examples: 'win product' matches 'win95 product', 'windows production line'
     * but not 'windows new product' or 'product of win'.
     */
    const PREFIX_WITH_ORDER_RESTRICTED_MATCH = 2;
    /**
     * This type of the match is supported for QUERY_STRING search query only.
     * Examples: 'win product' matches 'win product', 'new win product'
     * but not 'win95 product', 'win new product' or 'product of win'.
     */
    const EXACT_WITH_ORDER_RESTRICTED_MATCH = 3;
    /**
     * This type of the match is supported for RESTRICTION search query only.
     * Checks the property value and the supplied constant are the same.
     * Examples: 'win product' matches 'win product' only, but not 'new win product', 'win product line'.
     */
    const FULL_STRING_MATCH = 4;
    /**
     * This type of the match is supported for RESTRICTION search query only.
     * Checks the substring exists anywhere in the property value.
     */
    const SUBSTRING_MATCH = 5;
}
