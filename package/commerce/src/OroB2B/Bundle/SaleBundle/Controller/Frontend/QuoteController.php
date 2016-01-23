<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_frontend_view", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend:view.html.twig")
     * @Acl(
     *      id="orob2b_sale_quote_frontend_view",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @Route("/", name="orob2b_sale_quote_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="orob2b_sale_quote_frontend_index",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_sale_quote_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_sale_quote_frontend_view")
     *
     * @param Quote $quote
     * @return array
     */
    public function infoAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }
}
