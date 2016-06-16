<?php

namespace OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where;

abstract class AbstractWherePartBuilder
{
    /**
     * @var array
     */
    protected $supporterOperators = [];

    /**
     * @param string $operator
     * @return bool
     */
    public function isOperatorSupported($operator)
    {
        return in_array($operator, $this->supporterOperators);
    }

    /**
     * @param string $field
     * @param string $type
     * @param string $operator
     * @param mixed $value
     * @return array
     */
    abstract public function buildPart($field, $type, $operator, $value);
}
