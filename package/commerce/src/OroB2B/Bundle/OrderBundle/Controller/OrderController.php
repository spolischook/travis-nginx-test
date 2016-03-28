<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\OrderRequestHandler;
use OroB2B\Bundle\OrderBundle\Form\Handler\OrderHandler;

class OrderController extends AbstractOrderController
{
    /**
     * @Route("/view/{id}", name="orob2b_order_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="VIEW"
     * )
     *
     * @param Order $order
     *
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'entity' => $order,
            'totals' => $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($order),
            /** @todo: https://magecore.atlassian.net/browse/BB-1752 */
            'taxes' => $this->get('orob2b_tax.manager.tax_manager')->getTax($order)
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_order_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_order_view")
     *
     * @param Order $order
     *
     * @return array
     */
    public function infoAction(Order $order)
    {
        $sourceEntity = null;

        if ($order->getSourceEntityClass() && $order->getSourceEntityId()) {
            $sourceEntityManager = $this->get('oro_entity.doctrine_helper');
            $sourceEntity = $sourceEntityManager->getEntity(
                $order->getSourceEntityClass(),
                $order->getSourceEntityId()
            );
        }

        return [
            'order' => $order,
            'sourceEntity' => $sourceEntity
        ];
    }

    /**
     * @Route("/", name="orob2b_order_index")
     * @Template
     * @AclAncestor("orob2b_order_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_order.entity.order.class'),
        ];
    }

    /**
     * Create order form
     *
     * @Route("/create", name="orob2b_order_create")
     * @Template("OroB2BOrderBundle:Order:update.html.twig")
     * @Acl(
     *      id="orob2b_order_create",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="CREATE"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new Order(), $request);
    }

    /**
     * Edit order form
     *
     * @Route("/update/{id}", name="orob2b_order_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_update",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Order $order, Request $request)
    {
        return $this->update($order, $request);
    }

    /**
     * @param Order $order
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(Order $order, Request $request)
    {
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $order->setAccount($this->getOrderHandler()->getAccount());
            $order->setAccountUser($this->getOrderHandler()->getAccountUser());
        }

        $form = $this->createForm(OrderType::NAME, $order);
        $handler = new OrderHandler(
            $form,
            $request,
            $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($order))
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $order,
            $form,
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_update',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_view',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.order.controller.order.saved.message'),
            $handler,
            function (Order $order, FormInterface $form, Request $request) {
                return [
                    'form' => $form->createView(),
                    'entity' => $order,
                    'totals' => $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($order),
                    'isWidgetContext' => (bool)$request->get('_wid', false),
                    'isShippingAddressGranted' => $this->getOrderAddressSecurityProvider()
                        ->isAddressGranted($order, AddressType::TYPE_SHIPPING),
                    'isBillingAddressGranted' => $this->getOrderAddressSecurityProvider()
                        ->isAddressGranted($order, AddressType::TYPE_BILLING),
                    'tierPrices' => $this->getTierPrices($order),
                    'matchedPrices' => $this->getMatchedPrices($order),
                ];
            }
        );
    }

    /**
     * @return OrderRequestHandler
     */
    protected function getOrderHandler()
    {
        return $this->get('orob2b_order.model.order_request_handler');
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}
