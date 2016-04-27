<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_shipping_dimension_unit")
 * @ORM\Entity
 */
class DimensionUnit
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    protected $code;

    /**
     * @var array
     *
     * @ORM\Column(name="conversion_rates", type="array", nullable=true)
     */
    protected $conversionRates = [];

    /**
     * @param string $code
     *
     * @return $this
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

    /**
     * @param array $conversionRates
     *
     * @return DimensionUnit
     */
    public function setConversionRates(array $conversionRates = [])
    {
        $this->conversionRates = $conversionRates;

        return $this;
    }

    /**
     * @return array
     */
    public function getConversionRates()
    {
        return $this->conversionRates;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->code;
    }
}
