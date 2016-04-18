<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Represents a configuration of all sorters for an entity.
 */
class SortersConfig implements EntityConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\ExclusionPolicyTrait;

    /** a list of sorters */
    const FIELDS = EntityConfig::FIELDS;

    /** a type of the exclusion strategy that should be used for the sorters */
    const EXCLUSION_POLICY = EntityConfig::EXCLUSION_POLICY;

    /** exclude all fields are not configured explicitly */
    const EXCLUSION_POLICY_ALL = EntityConfig::EXCLUSION_POLICY_ALL;

    /** exclude only fields are marked as excluded */
    const EXCLUSION_POLICY_NONE = EntityConfig::EXCLUSION_POLICY_NONE;

    /** @var array */
    protected $items = [];

    /** @var SorterFieldConfig[] */
    private $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;
        $this->removeItemWithDefaultValue($result, self::EXCLUSION_POLICY, self::EXCLUSION_POLICY_NONE);

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldName => $field) {
                $fieldConfig                      = $field->toArray();
                $result[self::FIELDS][$fieldName] = !empty($fieldConfig) ? $fieldConfig : null;
            }
        }

        return $result;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            empty($this->items)
            && empty($this->fields);
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
        $this->fields = array_map(
            function ($field) {
                return clone $field;
            },
            $this->fields
        );
    }

    /**
     * Checks whether the configuration of at least one field exists.
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all fields.
     *
     * @return SorterFieldConfig[] [field name => config, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Checks whether the configuration of the sorter exists.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of the sorter.
     *
     * @param string $fieldName
     *
     * @return SorterFieldConfig|null
     */
    public function getField($fieldName)
    {
        return isset($this->fields[$fieldName])
            ? $this->fields[$fieldName]
            : null;
    }

    /**
     * Gets the configuration of existing sorter or adds new sorter for a given field.
     *
     * @param string $fieldName
     *
     * @return SorterFieldConfig
     */
    public function getOrAddField($fieldName)
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Adds the configuration of the sorter.
     *
     * @param string                 $fieldName
     * @param SorterFieldConfig|null $field
     *
     * @return SorterFieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new SorterFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the sorter.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }
}
