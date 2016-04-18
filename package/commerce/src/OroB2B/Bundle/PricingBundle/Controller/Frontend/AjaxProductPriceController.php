<?php

namespace OroB2B\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\PricingBundle\Controller\AbstractAjaxProductPriceController;

class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-account", name="orob2b_pricing_frontend_price_by_account")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductPricesByAccount(Request $request)
    {
        return parent::getProductPricesByAccount($request);
    }

    /**
     * @Route("/get-matching-price", name="orob2b_pricing_frontend_matching_price")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get('orob2b_pricing.provider.matching_price')->getMatchingPrices($lineItems);

        return new JsonResponse($matchedPrices);
    }

    /**
     * @Route("/get-product-units-by-currency", name="orob2b_pricing_frontend_units_by_pricelist")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductUnitsByCurrencyAction(Request $request)
    {
        return $this->getProductUnitsByCurrency(
            $this->get('orob2b_pricing.model.price_list_request_handler')->getPriceListByAccount(),
            $request,
            $this->getParameter('orob2b_pricing.entity.combined_product_price.class')
        );
    }
}
