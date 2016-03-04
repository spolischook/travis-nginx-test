<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;


use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutController extends Controller
{
    /**
     * Create checkout form
     *
     * @Route(
     *     "/{id}",
     *     name="orob2b_checkout_frontend_checkout",
     *     defaults={"id" = null},
     *     requirements={"id"="\d+"}
     * )
     * @ParamConverter(
     *     "checkout",
     *     class="OroB2BCheckoutBundle:Checkout",
     *     isOptional="true",
     *     options={"id" = "id"}
     *     )
     * @Layout(vars={"page"})
     * @Acl(
     *      id="orob2b_checkout_frontend_checkout",
     *      type="entity",
     *      class="OroB2BCheckoutBundle:Checkout",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Checkout|null $checkout
     * @param Request $request
     * @return array
     */
    public function checkoutAction(Request $request, Checkout $checkout = null)
    {
        if (!$checkout) {
            $checkout = new Checkout();
        }
        $page = $request->query->get('page', 1);
        $user = $this->getUser();
        $data = ['checkout' => $checkout, 'page' => $page];
        if ($page == 1) {
            $formBuilder = $this->createFormBuilder();
            $formBuilder->add(
                'saveAddress',
                'checkbox',
                [
                    'label' => 'Save Address',
                    'required' => false,
                ]
            );
            $formBuilder->add(
                'useAsShipAddress',
                'checkbox',
                [
                    'label' => 'Ship to this address',
                    'required' => false,
                ]
            );
            $checkout->setAccountUser($user);
            $formBuilder->add(
                'address',
                OrderAddressType::NAME,
                [
                    'object' => $checkout,
                    'addressType' => AddressType::TYPE_BILLING,
                    'isEditEnabled' => true
                ]
            );
            $data['form'] = new FormAccessor($formBuilder->getForm());
        }

        return [
            'page' => $page,
            'data' => $data,
        ];
    }
}
