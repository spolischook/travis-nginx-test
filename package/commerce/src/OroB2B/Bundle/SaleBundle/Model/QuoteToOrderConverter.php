<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalsProvider;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteToOrderConverter
{
    const FIELD_OFFER = 'offer';
    const FIELD_QUANTITY = 'quantity';

    /** @var OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /** @var SubtotalsProvider */
    protected $subtotalsProvider;

    /**
     * @param OrderCurrencyHandler $orderCurrencyHandler
     * @param SubtotalsProvider $subtotalsProvider
     */
    public function __construct(OrderCurrencyHandler $orderCurrencyHandler, SubtotalsProvider $subtotalsProvider)
    {
        $this->orderCurrencyHandler = $orderCurrencyHandler;
        $this->subtotalsProvider = $subtotalsProvider;
    }

    /**
     * @param Quote $quote
     * @param AccountUser|null $user
     * @param array|null $selectedOffers
     * @return Order
     */
    public function convert(Quote $quote, AccountUser $user = null, array $selectedOffers = null)
    {
        $accountUser = $user ?: $quote->getAccountUser();
        $account = $user ? $user->getAccount() : $quote->getAccount();

        $order = new Order();
        $order
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->setOwner($quote->getOwner())
            ->setOrganization($quote->getOrganization())
            ->setPoNumber($quote->getPoNumber())
            ->setShipUntil($quote->getShipUntil());

        if (!$selectedOffers) {
            foreach ($quote->getQuoteProducts() as $quoteProduct) {
                /** @var QuoteProductOffer $productOffer */
                $productOffer = $quoteProduct->getQuoteProductOffers()->first();

                $order->addLineItem($this->createOrderLineItem($productOffer));
            }
        } else {
            foreach ($selectedOffers as $selectedOffer) {
                /** @var QuoteProductOffer $offer */
                $offer = $selectedOffer[self::FIELD_OFFER];

                $order->addLineItem(
                    $this->createOrderLineItem($offer, (float)$selectedOffer[self::FIELD_QUANTITY])
                );
            }
        }

        $this->orderCurrencyHandler->setOrderCurrency($order);
        $this->fillSubtotals($order);

        return $order;
    }

    /**
     * @param QuoteProductOffer $quoteProductOffer
     * @param float|null $quantity
     * @return OrderLineItem
     */
    protected function createOrderLineItem(QuoteProductOffer $quoteProductOffer, $quantity = null)
    {
        $quoteProduct = $quoteProductOffer->getQuoteProduct();
        $freeFormTitle = null;
        $productSku = null;

        if ($quoteProduct->isTypeNotAvailable()) {
            $product = $quoteProduct->getProductReplacement();
            if ($quoteProduct->isProductReplacementFreeForm()) {
                $freeFormTitle = $quoteProduct->getFreeFormProductReplacement();
                $productSku = $quoteProduct->getProductReplacementSku();
            }
        } else {
            $product = $quoteProduct->getProduct();
            if ($quoteProduct->isProductFreeForm()) {
                $freeFormTitle = $quoteProduct->getFreeFormProduct();
                $productSku = $quoteProduct->getProductSku();
            }
        }

        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setFreeFormProduct($freeFormTitle)
            ->setProductSku($productSku)
            ->setProduct($product)
            ->setProductUnit($quoteProductOffer->getProductUnit())
            ->setQuantity($quantity ?: $quoteProductOffer->getQuantity())
            ->setPriceType($quoteProductOffer->getPriceType())
            ->setPrice($quoteProductOffer->getPrice())
            ->setFromExternalSource(true);

        return $orderLineItem;
    }

    /**
     * @param Order $order
     */
    protected function fillSubtotals(Order $order)
    {
        $subtotals = $this->subtotalsProvider->getSubtotals($order);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($subtotals as $subtotal) {
            try {
                $propertyAccessor->setValue($order, $subtotal->getType(), $subtotal->getAmount());
            } catch (NoSuchPropertyException $e) {
            }
        }
    }
}
