<?php

namespace OroCRMPro\Bundle\FusionCharts\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroCRM\Bundle\SalesBundle\Controller\Dashboard\DashboardController as BaseDashboardController;

class SalesFlowB2BController extends BaseDashboardController
{
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
        return parent::mySalesFlowB2BAction($widget);
    }
}
