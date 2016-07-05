<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ParameterBag;

abstract class PropertyMetadata extends ParameterBag
{
    /** the name of a property */
    const NAME = 'name';

    /** the data-type of a property */
    const DATA_TYPE = 'dataType';

    /** a flag indicates whether a property can be NULL */
    const NULLABLE = 'nullable';

    /**
     * PropertyMetadata constructor.
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = array_map(
            function ($value) {
                return is_object($value) ? clone $value : $value;
            },
            $this->items
        );
    }

    /**
     * Gets the name of a property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->get(self::NAME);
    }

    /**
     * Sets the name of a property.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->set(self::NAME, $name);

        return $this;
    }

    /**
     * Gets the data-type of a property.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->get(self::DATA_TYPE);
    }

    /**
     * Sets the data-type of a property.
     *
     * @param string $dataType
     *
     * @return self
     */
    public function setDataType($dataType)
    {
        $this->set(self::DATA_TYPE, $dataType);

        return $this;
    }

    /**
     * Whether a property can be NULL.
     *
     * @return bool
     */
    public function isNullable()
    {
        return (bool)$this->get(self::NULLABLE);
    }

    /**
     * Sets a flag indicates whether a property can be NULL.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setIsNullable($value)
    {
        $this->set(self::NULLABLE, $value);

        return $this;
    }
}
