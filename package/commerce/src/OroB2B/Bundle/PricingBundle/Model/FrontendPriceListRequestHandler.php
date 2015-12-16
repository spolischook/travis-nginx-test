<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendPriceListRequestHandler extends AbstractPriceListRequestHandler
{
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrency';
    const SAVE_STATE_KEY = 'saveState';

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     * @param SecurityFacade $securityFacade
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session,
        SecurityFacade $securityFacade,
        PriceListTreeHandler $priceListTreeHandler
    ) {
        parent::__construct($requestStack);
        $this->session = $session;
        $this->securityFacade = $securityFacade;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceList()
    {
        $priceList = $this->priceListTreeHandler->getPriceList($this->getAccountUser());

        if (!$priceList) {
            throw new \RuntimeException('PriceList not found');
        }

        return $priceList;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceListSelectedCurrencies()
    {
        $priceListCurrencies = $this->getPriceList()->getCurrencies();
        $currency = null;

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $currency = $request->get(self::PRICE_LIST_CURRENCY_KEY);
        }

        if (!$currency && $this->session->has(self::PRICE_LIST_CURRENCY_KEY)) {
            $currency = $this->session->get(self::PRICE_LIST_CURRENCY_KEY);
        }

        if (in_array($currency, $priceListCurrencies, true)) {
            if ($request && $request->get(self::SAVE_STATE_KEY)) {
                $this->session->set(self::PRICE_LIST_CURRENCY_KEY, $currency);
            }

            return [$currency];
        }

        return (array)reset($priceListCurrencies);
    }

    /**
     * @return bool
     */
    public function getShowTierPrices()
    {
        $showTierPrices = parent::getShowTierPrices();

        $request = $this->requestStack->getCurrentRequest();

        if ((!$request || ($request && !$request->get(self::TIER_PRICES_KEY)))
            && $this->session->has(self::TIER_PRICES_KEY)
        ) {
            $showTierPrices = $this->session->get(self::TIER_PRICES_KEY);
        }

        if (is_string($showTierPrices)) {
            $showTierPrices = filter_var($showTierPrices, FILTER_VALIDATE_BOOLEAN);
        } else {
            $showTierPrices = (bool)$showTierPrices;
        }

        if ($request && $request->get(self::SAVE_STATE_KEY)) {
            $this->session->set(self::TIER_PRICES_KEY, $showTierPrices);
        }

        return $showTierPrices;
    }

    /**
     * @return null|AccountUser
     */
    protected function getAccountUser()
    {
        $accountUser = $this->securityFacade->getLoggedUser();

        return $accountUser instanceof AccountUser ? $accountUser : null;
    }
}
