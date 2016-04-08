<?php

namespace OroB2B\Bundle\OrderBundle\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\OrderBundle\Controller\AbstractOrderController;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\OrderBundle\Form\Handler\OrderHandler;

class OrderController extends AbstractOrderController
{
    /**
     * @Route("/", name="orob2b_order_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="orob2b_order_frontend_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
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
     * @Route("/view/{id}", name="orob2b_order_frontend_view", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_order_frontend_view")
     * @Layout()
     *
     * @param Order $order
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'data' => [
                'order' => $order,
                'totals' => (object)$this->getTotalProcessor()->getTotalWithSubtotalsAsArray($order),
            ],
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_order_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BOrderBundle:Order/Frontend:info.html.twig")
     * @AclAncestor("orob2b_order_frontend_view")
     *
     * @param Order $order
     * @return array
     */
    public function infoAction(Order $order)
    {
        return [
            'order' => $order,
        ];
    }

    /**
     * Create order form
     *
     * @Route("/create", name="orob2b_order_frontend_create")
     * @Template("OroB2BOrderBundle:Order/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_order_frontend_create",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $order = new Order();
        $order->setWebsite($this->get('orob2b_website.manager')->getCurrentWebsite());

        return $this->update($order, $request);
    }

    /**
     * Edit order form
     *
     * @Route("/update/{id}", name="orob2b_order_frontend_update", requirements={"id"="\d+"})
     * @Template("OroB2BOrderBundle:Order/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_order_frontend_update",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     *
     * @param Order $order
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Order $order, Request $request)
    {
        return $this->update($order, $request);
    }

    /**
     * Success order
     *
     * @Route("/success/{id}", name="orob2b_order_frontend_success", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="orob2b_order_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @return array
     */
    public function successAction(Order $order)
    {
        return [
            'data' => [
                'order' => $order,
            ],
        ];
    }

    /**
     * @param Order $order
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(Order $order, Request $request)
    {
        if (!$order->getAccountUser()) {
            $accountUser = $this->get('oro_security.security_facade')->getLoggedUser();
            if (!$accountUser instanceof AccountUser) {
                throw new \InvalidArgumentException('Only AccountUser can create an Order.');
            }

            $order->setAccountUser($accountUser);
        }

        if ($order->getAccount()) {
            $paymentTerm = $this->get('orob2b_payment.provider.payment_term')->getPaymentTerm($order->getAccount());

            if ($paymentTerm) {
                $order->setPaymentTerm($paymentTerm);
            }
        }

        //TODO: set correct owner in task BB-929
        if (!$order->getOwner()) {
            $user = $this->getDoctrine()->getManagerForClass('OroUserBundle:User')
                ->getRepository('OroUserBundle:User')
                ->findOneBy([]);

            $order->setOwner($user);
        }

        $form = $this->createForm(FrontendOrderType::NAME, $order);
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
                    'route' => 'orob2b_order_frontend_update',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_frontend_view',
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
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}
