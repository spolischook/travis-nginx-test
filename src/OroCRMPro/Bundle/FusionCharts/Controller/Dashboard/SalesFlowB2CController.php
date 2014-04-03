<?php

namespace OroCRMPro\Bundle\FusionCharts\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroCRM\Bundle\MagentoBundle\Controller\Dashboard\DashboardController as BaseDashboardController;

class SalesFlowB2CController extends BaseDashboardController
{
    /**
     * @Route(
     *      "/sales_flow_b2c/chart/{widget}",
     *      name="orocrmpro_fusioncharts_dashboard_sales_flow_b2c_chart",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("OroCRMProFusionChartsBundle:Dashboard:salesFlowChart.html.twig")
     */
    public function mySalesFlowB2CAction($widget)
    {
        $result = parent::mySalesFlowB2CAction($widget);

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
