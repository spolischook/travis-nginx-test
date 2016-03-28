<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;

class AjaxEntityTotalsController extends Controller
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityClassName}/{entityId}",
     *      name="orob2b_pricing_entity_totals",
     *      requirements={"entityId"="\d+"},
     *      defaults={"entityId"=0, "entityClassName"=""}
     * )
     *
     * @param string $entityClassName
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function getEntityTotalsAction($entityClassName, $entityId)
    {
        $totals = [];

        try {
            $totalRequestHandler = $this->get('orob2b_pricing.subtotal_processor.handler.request_handler');
            $totals = $totalRequestHandler->recalculateTotals($entityClassName, $entityId);
        } catch (EntityNotFoundException $e) {
            $this->createNotFoundException();
        }

        return new JsonResponse(
            $totals
        );
    }

    /**
     * @Route(
     *      "/recalculate-totals-for-entity/{entityClassName}/{entityId}",
     *      name="orob2b_pricing_recalculate_entity_totals",
     *      defaults={"entityId"=0, "entityClassName"=""}
     * )
     *
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string $entityClassName
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function recalculateTotalsAction(Request $request, $entityClassName, $entityId)
    {
        $totals = [];

        try {
            $totalRequestHandler = $this->get('orob2b_pricing.subtotal_processor.handler.request_handler');
            $totals = $totalRequestHandler->recalculateTotals($entityClassName, $entityId, $request);
        } catch (EntityNotFoundException $e) {
            $this->createNotFoundException();
        }

        return new JsonResponse(
            $totals
        );
    }
}
