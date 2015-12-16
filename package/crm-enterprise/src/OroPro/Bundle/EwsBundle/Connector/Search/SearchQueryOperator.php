<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

/**
 * Provides a list of operators use in SearchQuery class
 */
class SearchQueryOperator
{
    /** 'equals' operator. */
    const EQ = '';
    /** 'not equals' operator. */
    const NEQ = '- ';
    /** 'less than' operator. */
    const LT = '<';
    /** 'less than or equal' operator. */
    const LE = '<=';
    /** 'greater than' operator. */
    const GT = '>';
    /** 'greater than or equal' operator. */
    const GE = '>=';
}
