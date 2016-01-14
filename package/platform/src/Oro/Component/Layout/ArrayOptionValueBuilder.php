<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Exception\InvalidArgumentException;

class ArrayOptionValueBuilder implements OptionValueBuilderInterface
{
    /** @var array */
    protected $values = [];

    /**
     * @param $value
     * @return array
     * @throws UnexpectedTypeException
     */
    protected function prepareValueType($value)
    {
        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array', 'value');
        }

        return array_values($value);
    }

    /**
     * @inheritdoc
     */
    public function add($value)
    {
        if (!($value = $this->prepareValueType($value))) {
            return;
        }

        $this->values = array_merge($this->values, $value);
    }

    /**
     * @inheritdoc
     */
    public function remove($value)
    {
        if (!($value = $this->prepareValueType($value))) {
            return;
        }

        $this->values = array_values(array_diff($this->values, $value));
    }

    /**
     * @inheritdoc
     */
    public function replace($oldValues, $newValue)
    {
        if (!($value = $this->prepareValueType($oldValues))) {
            return;
        }

        if (!($value = $this->prepareValueType($newValue))) {
            return;
        }

        if (count($oldValues) !== count($newValue)) {
            throw new InvalidArgumentException(sprintf('$oldValues should be the same as $newValue size.'));
        }

        foreach ($oldValues as $index => $oldValue) {
            $key = array_search($oldValue, $this->values, true);
            if (false !== $key) {
                $this->values[$key] = $newValue[$index];
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->values;
    }
}
