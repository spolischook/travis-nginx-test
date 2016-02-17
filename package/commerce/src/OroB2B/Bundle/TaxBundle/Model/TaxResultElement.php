<?php

namespace OroB2B\Bundle\TaxBundle\Model;

class TaxResultElement extends AbstractResultElement
{
    const TAX = 'tax';
    const RATE = 'rate';
    const TAXABLE_AMOUNT = 'taxable_amount';
    const TAX_AMOUNT = 'tax_amount';

    /**
     * @param string $taxCode
     * @param string $rate
     * @param string $taxableAmount
     * @param string $taxAmount
     * @return TaxResultElement
     */
    public static function create($taxCode, $rate, $taxableAmount, $taxAmount)
    {
        $resultElement = new static;

        $resultElement->offsetSet(self::TAX, $taxCode);
        $resultElement->offsetSet(self::RATE, $rate);
        $resultElement->offsetSet(self::TAXABLE_AMOUNT, $taxableAmount);
        $resultElement->offsetSet(self::TAX_AMOUNT, $taxAmount);

        return $resultElement;
    }

    /**
     * @return string
     */
    public function getTax()
    {
        return $this->getOffset(self::TAX);
    }

    /**
     * @return string
     */
    public function getRate()
    {
        return $this->getOffset(self::RATE);
    }

    /**
     * @return string
     */
    public function getTaxableAmount()
    {
        return $this->getOffset(self::TAXABLE_AMOUNT);
    }

    /**
     * @return string
     */
    public function getTaxAmount()
    {
        return $this->getOffset(self::TAX_AMOUNT);
    }
}
