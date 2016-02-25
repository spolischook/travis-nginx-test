<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountWebsiteScopedPriceListsType extends AbstractWebsiteScopedPriceListsType
{
    const NAME = 'orob2b_pricing_account_website_scoped_price_lists';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param Account $account
     * @return BasePriceListRelation
     */
    protected function createPriceListToTargetEntity($account)
    {
        $priceListToTargetEntity = new PriceListToAccount();
        $priceListToTargetEntity->setAccount($account);

        return $priceListToTargetEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassName()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackChoices()
    {
        return [
            PriceListAccountFallback::ACCOUNT_GROUP =>
                'orob2b.pricing.fallback.account_group.label',
            PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
                'orob2b.pricing.fallback.current_account_only.label',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetFieldName()
    {
        return 'account';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackClassName()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFallback()
    {
        return PriceListAccountFallback::ACCOUNT_GROUP;
    }
}
