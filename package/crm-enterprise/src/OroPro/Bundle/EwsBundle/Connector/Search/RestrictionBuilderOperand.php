<?php

namespace OroPro\Bundle\EwsBundle\Connector\Search;

class RestrictionBuilderOperand
{
    /**
     * @param string $type
     * @param mixed $element
     */
    public function __construct($type, $element)
    {
        $this->type = $type;
        $this->element = $element;

    }

    /**
     * The type of the operand
     *
     * @var string
     */
    private $type;

    /**
     * The EWS element represents the operand
     *
     * @var mixed
     */
    private $element;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param mixed $element
     */
    public function setElement($element)
    {
        $this->element = $element;
    }
}
