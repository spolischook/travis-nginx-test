<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

class SearchQueryExprItem extends SearchQueryExprValueBase implements
    SearchQueryExprNamedItemInterface,
    SearchQueryExprValueInterface,
    SearchQueryExprInterface
{
    /**
     * @param string $name The property name
     * @param string|SearchQueryExpr $value The word phrase
     * @param string $operator The comparison operator. One of SearchQueryOperator::* values
     * @param int $match The match type. One of SearchQueryMatch::* values
     * @param bool $ignoreCase
     */
    public function __construct($name, $value, $operator, $match, $ignoreCase)
    {
        parent::__construct($value, $match);
        $this->name = $name;
        $this->operator = $operator;
        $this->ignoreCase = $ignoreCase;
    }

    /**
     * The name of a property
     *
     * @var string
     */
    private $name;

    /**
     * A comparison operator
     *
     * @var SearchQueryOperator
     */
    private $operator;

    /**
     * Determines whether case sensitive match or not
     *
     * @var bool
     */
    private $ignoreCase;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     * @see SearchQueryOperator
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     * @see SearchQueryOperator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @return bool
     */
    public function getIgnoreCase()
    {
        return $this->ignoreCase;
    }

    /**
     * @param bool $ignoreCase
     */
    public function setIgnoreCase($ignoreCase)
    {
        $this->ignoreCase = $ignoreCase;
    }
}
