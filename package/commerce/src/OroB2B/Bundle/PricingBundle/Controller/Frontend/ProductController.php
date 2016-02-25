<?php

namespace OroB2B\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="orob2b_pricing_frontend_product_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        return [
            'currencies' => $this->createCurrenciesForm()->createView(),
            'showTierPrices' => $this->createShowTierPricesForm()->createView(),
        ];
    }

    /**
     * @return Form
     */
    protected function createCurrenciesForm()
    {
        $accountUser = $this->getUser();
        if (!$accountUser instanceof AccountUser) {
            $accountUser = null;
        }

        $priceList = $this->get('orob2b_pricing.model.price_list_tree_handler')
            ->getPriceList($accountUser->getAccount());

        $currenciesList = null;
        $selectedCurrencies = [];
        if ($priceList) {
            if ($priceList->getCurrencies()) {
                $currenciesList = $priceList->getCurrencies();
            }
            $selectedCurrencies = $this->getHandler()->getPriceListSelectedCurrencies($priceList);
        }

        $formOptions = [
            'label' => 'orob2b.pricing.productprice.currency.label',
            'compact' => true,
            'required' => false,
            'empty_value' => false,
            'currencies_list' => $currenciesList,
            'data' => reset($selectedCurrencies),
        ];

        return $this->createForm(CurrencySelectionType::NAME, null, $formOptions);
    }

    /**
     * @return Form
     */
    protected function createShowTierPricesForm()
    {
        return $this->createForm(
            'checkbox',
            null,
            [
                'label' => 'orob2b.pricing.productprice.show_tier_prices.label',
                'required' => false,
                'data' => $this->getHandler()->getShowTierPrices(),
            ]
        );
    }

    /**
     * @return PriceListRequestHandler
     */
    protected function getHandler()
    {
        return $this->get('orob2b_pricing.model.price_list_request_handler');
    }
}
