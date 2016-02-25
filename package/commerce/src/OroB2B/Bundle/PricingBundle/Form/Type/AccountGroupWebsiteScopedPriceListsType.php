<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;

class AccountGroupWebsiteScopedPriceListsType extends AbstractWebsiteScopedPriceListsType
{
    const NAME = 'orob2b_pricing_account_group_website_scoped_price_lists';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param AccountGroup $accountGroup
     * @return BasePriceListRelation
     */
    protected function createPriceListToTargetEntity($accountGroup)
    {
        $priceListToTargetEntity = new PriceListToAccountGroup();
        $priceListToTargetEntity->setAccountGroup($accountGroup);

        return $priceListToTargetEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassName()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackChoices()
    {
        return [
            PriceListAccountGroupFallback::WEBSITE =>
                'orob2b.pricing.fallback.website.label',
            PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                'orob2b.pricing.fallback.current_account_group_only.label',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetFieldName()
    {
        return 'accountGroup';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackClassName()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFallback()
    {
        return PriceListAccountGroupFallback::WEBSITE;
    }
}
