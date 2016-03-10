<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * All the supported data-types of an incoming values which implemented by out of the box.
 * New data-types can be added by implementing a value normalization processors.
 * @see Oro\Bundle\ApiBundle\Request\ValueNormalizer
 */
final class DataType
{
    const INTEGER             = 'integer';
    const UNSIGNED_INTEGER    = 'unsignedInteger';
    const STRING              = 'string';
    const BOOLEAN             = 'boolean';
    const DATETIME            = 'datetime';
    const ENTITY_TYPE         = 'entityType';
    const ENTITY_CLASS        = 'entityClass';
    const ENTITY_ALIAS        = 'entityAlias';
    const ENTITY_PLURAL_ALIAS = 'entityPluralAlias';
    const ORDER_BY            = 'orderBy';
}
