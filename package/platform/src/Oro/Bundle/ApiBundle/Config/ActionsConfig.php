<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;

class ActionsConfig
{
    use Traits\ConfigTrait;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Gets action configs
     *
     * @param string $action
     *
     * @return array
     */
    public function getAction($action)
    {
        return $this->has($action) ? $this->get($action) : [];
    }

    public function isActionEnabled($action)
    {
    }
}
