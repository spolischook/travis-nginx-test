<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class StubProduct extends Product
{
    /**
     * @var AbstractEnumValue
     */
    private $inventoryStatus;

    /**
     * @var mixed
     */
    private $visibility = [];

    /**
     * @var string
     */
    private $size;

    /**
     * @var string
     */
    private $color;

    /**
     * @return AbstractEnumValue
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param mixed $visibility
     * @return AbstractEnumValue
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return AbstractEnumValue
     */
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * @param AbstractEnumValue $inventoryStatus
     * @return $this
     */
    public function setInventoryStatus(AbstractEnumValue $inventoryStatus)
    {
        $this->inventoryStatus = $inventoryStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }
}
