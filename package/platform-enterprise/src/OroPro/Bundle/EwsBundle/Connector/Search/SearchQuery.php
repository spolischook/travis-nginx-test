<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

/**
 * Represents a search query for Exchange Web Services (EWS).
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SearchQuery
{
    const AUTO = 0;
    const QUERY_STRING = 1;
    const RESTRICTION = 2;

    private static $supportedQueryStringFields =
        array('from', 'to', 'cc', 'bcc', 'participants', 'subject', 'body', 'attachment', 'sent', 'received', 'kind');

    /**
     * @var SearchQueryExpr
     */
    private $expr;

    /** @var int Can be one of AUTO, QUERY_STRING or RESTRICTION */
    private $type;

    /** @var QueryStringBuilder */
    private $queryStringBuilder;

    /** @var RestrictionBuilder */
    private $restrictionBuilder;

    /**
     * Creates SearchQuery object.
     */
    public function __construct(QueryStringBuilder $queryStringBuilder, RestrictionBuilder $restrictionBuilder)
    {
        $this->queryStringBuilder = $queryStringBuilder;
        $this->restrictionBuilder = $restrictionBuilder;
        $this->expr = new SearchQueryExpr();
        $this->type = SearchQuery::AUTO;
    }

    /**
     * Creates new empty instance of this class.
     *
     * @return mixed
     */
    public function newInstance()
    {
        $calledClass = get_called_class();

        return new $calledClass($this->queryStringBuilder, $this->restrictionBuilder);
    }

    /**
     * Gets the expression represents the search query.
     *
     * @return SearchQueryExpr
     */
    public function getExpression()
    {
        return $this->expr;
    }

    /**
     * Adds a word phrase to be searched in all properties.
     *
     * @param string|SearchQuery $value The word phrase
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @throws \InvalidArgumentException
     */
    public function value($value, $match = SearchQueryMatch::DEFAULT_MATCH)
    {
        if (($value instanceof SearchQuery) && $value->isComplex()) {
            if ($match != SearchQueryMatch::DEFAULT_MATCH) {
                throw new \InvalidArgumentException(
                    "The match argument can be specified only if the value argument is a string or a simple query."
                );
            }
        }
        if ($this->type === SearchQuery::AUTO) {
            $this->type = SearchQuery::QUERY_STRING;
        } elseif ($this->type === SearchQuery::RESTRICTION) {
            throw new \InvalidArgumentException("The search in all properties is not supported for RESTRICTION query.");
        }
        $this->andOperatorIfNeeded();
        $expr = $value instanceof SearchQuery
            ? $value->getExpression()
            : new SearchQueryExprValue($value, $match);
        $this->expr->add($expr);
    }

    /**
     * Adds name/value pair specifying a word phrase and property where it need to be searched.
     *
     * @param string $name The property name
     * @param string|SearchQuery $value The word phrase
     * @param string $operator The value comparison operator.
     * @see SearchQueryOperator
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @param bool $ignoreCase Set false to case sensitive search
     * @throws \InvalidArgumentException
     */
    public function item(
        $name,
        $value,
        $operator = SearchQueryOperator::EQ,
        $match = SearchQueryMatch::DEFAULT_MATCH,
        $ignoreCase = true
    ) {
        if (($value instanceof SearchQuery) && $value->isComplex()) {
            if ($match != SearchQueryMatch::DEFAULT_MATCH) {
                throw new \InvalidArgumentException(
                    "The match argument can be specified only if the value argument is a string or a simple query."
                );
            }
            if ($operator != SearchQueryOperator::EQ) {
                throw new \InvalidArgumentException(
                    "The operator argument can be specified only if the value argument is a string or a simple query."
                );
            }
        }

        $this->changeQueryTypeIfNeeded($name, $match, $ignoreCase);
        $this->validateQueryType($match);

        $this->andOperatorIfNeeded();
        $value = $value instanceof SearchQuery
            ? $value->getExpression()
            : $value;
        $this->expr->add(new SearchQueryExprItem($name, $value, $operator, $match, $ignoreCase));
    }

    /**
     * @param string $name The property name
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @param bool $ignoreCase
     */
    private function changeQueryTypeIfNeeded($name, $match, $ignoreCase)
    {
        if ($this->type === SearchQuery::AUTO) {
            if (!$ignoreCase || !in_array($name, self::$supportedQueryStringFields)) {
                $this->type = SearchQuery::RESTRICTION;
            }
            if ($match !== SearchQueryMatch::DEFAULT_MATCH) {
                if ($match <= SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH) {
                    $this->type = SearchQuery::QUERY_STRING;
                } else {
                    $this->type = SearchQuery::RESTRICTION;
                }
            }
        }
    }

    /**
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     * @throws \InvalidArgumentException
     */
    private function validateQueryType($match)
    {
        if ($match !== SearchQueryMatch::DEFAULT_MATCH) {
            if ($this->type === SearchQuery::QUERY_STRING) {
                if ($match > SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH) {
                    throw new \InvalidArgumentException("Incorrect match argument for QUERY_STRING query type.");
                }
            } elseif ($this->type === SearchQuery::RESTRICTION) {
                if ($match <= SearchQueryMatch::EXACT_WITH_ORDER_RESTRICTED_MATCH) {
                    throw new \InvalidArgumentException("Incorrect match argument for RESTRICTION query type.");
                }
            }
        }
    }

    /**
     * Adds name/value pair specifying a value range and property where it need to be searched.
     *
     * @param string $name The property name
     * @param string $fromValue The start value in the range
     * @param string $toValue The end value in the range
     * @throws \InvalidArgumentException
     */
    public function itemRange($name, $fromValue, $toValue)
    {
        if ($fromValue === null || $fromValue == '') {
            throw new \InvalidArgumentException("The fromValue argument must not be empty.");
        }
        if ($toValue === null || $toValue == '') {
            throw new \InvalidArgumentException("The toValue argument must not be empty.");
        }
        if ($this->type === SearchQuery::AUTO) {
            if (!in_array($name, self::$supportedQueryStringFields)) {
                $this->type = SearchQuery::RESTRICTION;
            }
        }
        $this->andOperatorIfNeeded();
        $this->expr->add(new SearchQueryExprRangeItem($name, $fromValue, $toValue));
    }

    /**
     * Adds AND operator.
     */
    public function andOperator()
    {
        $this->expr->add(new SearchQueryExprOperator('AND'));
    }

    /**
     * Adds OR operator.
     */
    public function orOperator()
    {
        $this->expr->add(new SearchQueryExprOperator('OR'));
    }

    /**
     * Adds NOT operator.
     */
    public function notOperator()
    {
        $this->andOperatorIfNeeded();
        $this->expr->add(new SearchQueryExprOperator('NOT'));
    }

    /**
     * Adds open parenthesis '('.
     */
    public function openParenthesis()
    {
        $this->andOperatorIfNeeded();
        $this->expr->add(new SearchQueryExprOperator('('));
    }

    /**
     * Adds close parenthesis ')'.
     */
    public function closeParenthesis()
    {
        $this->expr->add(new SearchQueryExprOperator(')'));
    }

    /**
    Gets the query type
     *
     * @return int Can be one of AUTO, QUERY_STRING or RESTRICTION
     */
    public function getQueryType()
    {
        return $this->type;
    }

    /**
     * Sets the query type
     *
     * @param int $type Can be one of AUTO, QUERY_STRING or RESTRICTION
     */
    public function setQueryType($type)
    {
        $this->type = $type;
    }

    /**
     * Builds a string contains human readable representation of the search query.
     *
     * @return string
     */
    public function convertToString()
    {
        $converter = new SearchQueryToStringConverter();

        return $converter->buildString($this->expr);
    }

    /**
     * Builds a string representation of the search query which can be passed to Exchange Web Services (EWS).
     *
     * @return string
     * @throws \LogicException
     */
    public function convertToQueryString()
    {
        if ($this->type === SearchQuery::RESTRICTION) {
            throw new \LogicException('Only AUTO or QUERY_STRING search query can be converted to a query string.');
        }

        return $this->queryStringBuilder->buildQueryString($this->expr);
    }

    /**
     * Builds the Exchange Web Services (EWS) RestrictionType object represents the search query.
     *
     * @return EwsType\RestrictionType
     * @throws \LogicException
     */
    public function convertToRestriction()
    {
        if ($this->type === SearchQuery::QUERY_STRING) {
            throw new \LogicException(
                'Only AUTO or RESTRICTION search query can be converted to a restriction object.'
            );
        }

        return $this->restrictionBuilder->buildRestriction($this->expr);
    }

    /**
     * Checks if this query has no any expressions.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->expr->isEmpty();
    }

    /**
     * Checks if this query has more than one expression.
     *
     * @return bool
     */
    public function isComplex()
    {
        return $this->expr->isComplex();
    }

    private function andOperatorIfNeeded()
    {
        $exprItems = $this->expr->getItems();
        $lastIndex = count($exprItems) - 1;
        if ($lastIndex != -1) {
            $lastItem = $exprItems[$lastIndex];
            if (!($lastItem instanceof SearchQueryExprOperator)
                || (($lastItem instanceof SearchQueryExprOperator) && $lastItem->getName() == ')')
            ) {
                $this->andOperator();
            }
        }
    }
}
