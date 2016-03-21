<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class EntityWithoutCurrencyStub implements LineItemsAwareInterface
{
    /**
     * @var ArrayCollection
     */
    protected $lineItems;

    /**
     * @return ArrayCollection
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param LineItemStub $lineItem
     * @return EntityStub
     */
    public function addLineItem(LineItemStub $lineItem)
    {
        $this->lineItems[] = $lineItem;

        return $this;
    }
}
