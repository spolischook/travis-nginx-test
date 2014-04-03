<?php

namespace OroCRMPro\Bundle\FusionCharts\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroCRM\Bundle\SalesBundle\Controller\Dashboard\DashboardController as BaseDashboardController;

class SalesFlowB2BController extends BaseDashboardController
{
    /**
     * @Route(
     *      "/opportunities_by_lead_source/chart/{widget}",
     *      name="orocrmpro_fusioncharts_dashboard_opportunities_by_lead_source_chart",
     *      requirements={"widget"="[\w-]+"}
     * )
     * @Template("OroCRMProFusionChartsBundle:Dashboard:pieChart.html.twig")
     */
    public function opportunitiesByLeadSourceAction($widget)
    {
        $result = parent::opportunitiesByLeadSourceAction($widget);

        $hasData = false;
        $data    = [];
        foreach ($result['data'] as $record) {
            $value = $record['itemCount'];

            if ($value) {
                $hasData = true;
            }

            $data[] = [
                'label' => $record['label'],
                'value' => $value
            ];
        }
        $result['data']    = $data;
        $result['hasData'] = $hasData;

        return $result;
    }

    /**
     * @Route(
     *      "/opportunity_state/chart/{widget}",
     *      name="orocrmpro_fusioncharts_dashboard_opportunity_by_state_chart",
     *      requirements={"widget"="[\w-]+"}
     * )
     * @Template("OroCRMProFusionChartsBundle:Dashboard:opportunityByStatus.html.twig")
     */
    public function opportunityByStatusAction($widget)
    {
        $result = parent::opportunityByStatusAction($widget);

        $hasData = false;
        $data    = [];
        foreach ($result['items']['data'] as $key => $record) {
            $value = $record[1];

            if ($value) {
                $hasData = true;
            }

            $data[] = [
                'label' => $result['items']['labels'][$key],
                'value' => $value
            ];
        }
        $result['items']['data'] = $data;
        $result['hasData']       = $hasData;

        return $result;
    }

    /**
     * @Route(
     *      "/sales_flow_b2b/chart/{widget}",
     *      name="orocrmpro_fusioncharts_dashboard_sales_flow_b2b_chart",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("OroCRMProFusionChartsBundle:Dashboard:salesFlowChart.html.twig")
     */
    public function mySalesFlowB2BAction($widget)
    {
        $result = parent::mySalesFlowB2BAction($widget);

        $hasData = false;
        $data    = [];
        foreach ($result['items'] as $label => $value) {
            if ($value) {
                $hasData = true;
            }

            $data[] = [
                'label' => $label,
                'value' => $value
            ];
        }
        $result['items']   = $data;
        $result['hasData'] = $hasData;

        return $result;
    }
}
