<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

class SearchQueryExprRangeItem implements SearchQueryExprNamedItemInterface
{
    public function __construct($name, $fromValue, $toValue)
    {
        $this->name = $name;
        $this->fromValue = $fromValue;
        $this->toValue = $toValue;
    }

    /**
     * The name of a property
     *
     * @var string
     */
    private $name;

    /**
     * The first value for ranged search
     *
     * @var string
     */
    private $fromValue;

    /**
     * The last value for ranged search
     *
     * @var string
     */
    private $toValue;

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
     */
    public function getFromValue()
    {
        return $this->fromValue;
    }

    /**
     * @param string $value
     */
    public function setFromValue($value)
    {
        $this->fromValue = $value;
    }

    /**
     * @return string
     */
    public function getToValue()
    {
        return $this->toValue;
    }

    /**
     * @param string $value
     */
    public function setToValue($value)
    {
        $this->toValue = $value;
    }
}
