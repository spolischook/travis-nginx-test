<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class FrontendProductPricesDataProvider
{
    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param SecurityFacade $securityFacade
     * @param UserCurrencyProvider $userCurrencyProvider
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        SecurityFacade $securityFacade,
        UserCurrencyProvider $userCurrencyProvider,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->securityFacade = $securityFacade;
        $this->userCurrencyProvider = $userCurrencyProvider;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param LineItem[] $lineItems
     * @return array|null
     */
    public function getProductsPrices(array $lineItems)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $productsPriceCriteria = $this->getProductsPricesCriteria($lineItems);

        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPriceCriteria,
            $this->priceListRequestHandler->getPriceListByAccount()
        );

        $result = [];
        foreach ($prices as $key => $price) {
            $identifier = explode('-', $key);
            list($productId, $unitId) = $identifier;
            $result[$productId][$unitId] = $price;
        }

        return $result;
    }

    /**
     * @param Collection|LineItem[] $lineItems
     * @return array
     */
    protected function getProductsPricesCriteria(array $lineItems)
    {
        $productsPricesCriteria = [];
        foreach ($lineItems as $lineItem) {
            $productsPricesCriteria[] = new ProductPriceCriteria(
                $lineItem->getProduct(),
                $lineItem->getProductUnit(),
                $lineItem->getQuantity(),
                $this->userCurrencyProvider->getUserCurrency()
            );
        }

        return $productsPricesCriteria;
    }
}
