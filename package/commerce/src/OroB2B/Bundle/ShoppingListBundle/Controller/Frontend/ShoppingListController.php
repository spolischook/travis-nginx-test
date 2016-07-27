<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListController extends Controller
{
    /**
     * @Route("/{id}", name="orob2b_shopping_list_frontend_view", defaults={"id" = null}, requirements={"id"="\d+"})
     * @Layout(vars={"title"})
     * @Acl(
     *      id="orob2b_shopping_list_frontend_view",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param int|null $id
     *
     * @return array
     */
    public function viewAction($id = null)
    {
        /** @var ShoppingListRepository $repo */
        $repo = $this->getDoctrine()->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $shoppingList = $repo->findOneByIdWithRelations($id);

        if (!$shoppingList) {
            $user = $this->getUser();
            if ($user instanceof AccountUser) {
                $shoppingList = $repo->findAvailableForAccountUser($user, true);
            }
        }
        if ($shoppingList) {
            $title = $shoppingList->getLabel();
            $totalWithSubtotalsAsArray = $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($shoppingList);

            $lineItems = $shoppingList->getLineItems();

            if (!empty($lineItems)) {
                $products = [];
                foreach ($lineItems as $lineItem) {
                    /** @var LineItem $lineItem */
                    $products[]['productSku'] = $lineItem->getProduct()->getSku();
                }
                if (!empty($this->container)) {
                    $shoppingList->setIsAllowedRFP(
                        $this->container
                            ->get('orob2b_rfp.form.type.extension.frontend_request_data_storage')
                            ->isAllowedRFP($products)
                    );
                }
            }
        } else {
            $title = null;
            $totalWithSubtotalsAsArray = [];
        }

        return [
            'title' => $title,
            'data' => [
                'entity' => $shoppingList,
                'totals' => [
                    'identifier' => 'totals',
                    'data' => $totalWithSubtotalsAsArray
                ]
            ],
        ];
    }

    /**
     * Create shopping list form
     *
     * @Route("/create", name="orob2b_shopping_list_frontend_create")
     * @Layout
     * @Acl(
     *      id="orob2b_shopping_list_frontend_create",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @return array|Response
     */
    public function createAction(Request $request)
    {
        $shoppingListManager = $this->get('orob2b_shopping_list.shopping_list.manager');
        $shoppingList = $shoppingListManager->create();

        $response = $this->create($request, $shoppingList);
        if ($response instanceof Response) {
            return $response;
        }

        $defaultResponse = [
            'savedId' => null,
            'shoppingList' => $shoppingList,
            'createOnly' => $request->get('createOnly')
        ];

        return ['data' => array_merge($defaultResponse, $response)];
    }

    /**
     * @param Request $request
     * @param ShoppingList $shoppingList
     *
     * @return array|Response
     */
    protected function create(Request $request, ShoppingList $shoppingList)
    {
        $form = $this->createForm(ShoppingListType::NAME);

        $handler = new ShoppingListHandler(
            $form,
            $request,
            $this->get('orob2b_shopping_list.shopping_list.manager'),
            $this->getDoctrine()
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $shoppingList,
            $this->createForm(ShoppingListType::NAME, $shoppingList),
            function (ShoppingList $shoppingList) {
                return [
                    'route' => 'orob2b_shopping_list_frontend_view',
                    'parameters' => ['id' => $shoppingList->getId()]
                ];
            },
            function (ShoppingList $shoppingList) {
                return [
                    'route' => 'orob2b_shopping_list_frontend_view',
                    'parameters' => ['id' => $shoppingList->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.shoppinglist.controller.shopping_list.saved.message'),
            $handler
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
