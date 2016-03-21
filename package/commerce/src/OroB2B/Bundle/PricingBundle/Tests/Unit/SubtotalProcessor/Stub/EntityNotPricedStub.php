<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

class EntityNotPricedStub implements
    LineItemsNotPricedAwareInterface,
    CurrencyAwareInterface,
    AccountAwareInterface,
    WebsiteAwareInterface
{
    /**
     * @var ArrayCollection
     */
    protected $lineItems;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var Website
     */
    protected $website;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param LineItemNotPricedStub $lineItem
     *
     * @return EntityStub
     */
    public function addLineItem(LineItemNotPricedStub $lineItem)
    {
        $this->lineItems[] = $lineItem;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }
}
