<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Form\Type\EntityVisibilityType;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\AccountBundle\Form\Handler\WebsiteScopedDataHandler;

class ProductVisibilityController extends Controller
{
    /**
     * @Route("/edit/{id}", name="orob2b_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_product_update")
     *
     * @param Request $request
     * @param Product $product
     * @return array
     */
    public function editAction(Request $request, Product $product)
    {
        $form = $this->createWebsiteScopedDataForm(
            $product,
            [
                $this->getDoctrine()->getRepository('OroB2BWebsiteBundle:Website')->getDefaultWebsite()
            ]
        );

        $handler = new WebsiteScopedDataHandler($form, $request, $this->get('event_dispatcher'));

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $product,
            $form,
            function (Product $product) {
                return [
                    'route' => 'orob2b_product_visibility_edit',
                    'parameters' => ['id' => $product->getId()],
                ];
            },
            function (Product $product) {
                return [
                    'route' => 'orob2b_product_view',
                    'parameters' => ['id' => $product->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.account.visibility.event.saved.message'),
            $handler
        );
    }

    /**
     * @Route(
     *      "/edit/{productId}/website/{id}",
     *      name="orob2b_product_visibility_website",
     *      requirements={"productId"="\d+", "id"="\d+"}
     * )
     * @ParamConverter("product", options={"id" = "productId"})
     * @Template("OroB2BAccountBundle:ProductVisibility/widget:website.html.twig")
     * @AclAncestor("orob2b_product_update")
     *
     * @param Product $product
     * @param Website $website
     * @return array
     */
    public function websiteWidgetAction(Product $product, Website $website)
    {
        /** @var Form $form */
        $form = $this->createWebsiteScopedDataForm($product, [$website]);

        return [
            'form' => $form->createView()[$website->getId()],
            'entity' => $product,
            'website' => $website
        ];
    }

    /**
     * @param Product $product
     * @param array $preloaded_websites
     * @return Form
     */
    protected function createWebsiteScopedDataForm(Product $product, array $preloaded_websites)
    {
        return $this->createForm(
            WebsiteScopedDataType::NAME,
            $product,
            [
                'ownership_disabled' => true,
                'preloaded_websites' => $preloaded_websites,
                'type' => EntityVisibilityType::NAME,
                'options' => [
                    'targetEntityField' => 'product',
                    'allClass' => $this
                        ->getParameter('orob2b_account.entity.product_visibility.class'),
                    'accountGroupClass' => $this
                        ->getParameter('orob2b_account.entity.account_group_product_visibility.class'),
                    'accountClass' => $this
                        ->getParameter('orob2b_account.entity.account_product_visibility.class'),
                ]
            ]
        );
    }
}
