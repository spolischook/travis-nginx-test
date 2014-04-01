<?php

namespace OroCRMPro\Bundle\FusionCharts\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroCRM\Bundle\SalesBundle\Controller\Dashboard\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
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
        return parent::opportunitiesByLeadSourceAction($widget);
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
        return parent::opportunityByStatusAction($widget);
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
        return parent::mySalesFlowB2BAction($widget);
    }

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
        $dateTo = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateFrom = new \DateTime(
            $dateTo->format('Y') . '-01-' . ((ceil($dateTo->format('n') / 3) - 1) * 3 + 1),
            new \DateTimeZone('UTC')
        );

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getApplicableWorkflowByEntityClass(
            'OroCRM\Bundle\MagentoBundle\Entity\Cart'
        );

        /** @var CartRepository $shoppingCartRepository */
        $shoppingCartRepository = $this->getDoctrine()->getRepository('OroCRMMagentoBundle:Cart');

        return array_merge(
            array('quarterDate' => $dateFrom),
            $shoppingCartRepository->getFunnelChartData(
                $dateFrom,
                $dateTo,
                $workflow,
                $this->get('oro_security.acl_helper')
            ),
            $this->get('oro_dashboard.manager')->getWidgetAttributesForTwig($widget)
        );
    }
}
