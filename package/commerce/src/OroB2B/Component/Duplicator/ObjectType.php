<?php

namespace OroB2B\Component\Duplicator;

class ObjectType
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $keyword;

    /**
     * @param string $className
     * @param string $keyword
     */
    public function __construct($keyword, $className)
    {
        $this->keyword = $keyword;
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
}
