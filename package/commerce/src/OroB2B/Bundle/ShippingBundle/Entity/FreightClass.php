<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_shipping_freight_class")
 * @ORM\Entity
 */
class FreightClass
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    protected $code;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return FreightClass
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
}
