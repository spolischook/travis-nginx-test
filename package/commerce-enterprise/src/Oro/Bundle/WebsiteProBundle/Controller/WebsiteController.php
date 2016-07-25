<?php

namespace Oro\Bundle\WebsiteProBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteProBundle\Form\Type\WebsiteType;

class WebsiteController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_websitepro_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_websitepro_view",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="VIEW"
     * )
     *
     * @param Website $website
     * @return array
     */
    public function viewAction(Website $website)
    {
        return [
            'entity' => $website,
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_websitepro_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_websitepro_view")
     *
     * @param Website $website
     *
     * @return array
     */
    public function infoAction(Website $website)
    {
        return [
            'website' => $website,
        ];
    }

    /**
     * @Route("/", name="oro_websitepro_index")
     * @Template
     * @AclAncestor("oro_websitepro_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_website.entity.website.class')
        ];
    }

    /**
     * Create website
     *
     * @Route("/create", name="oro_websitepro_create")
     * @Template("OroWebsiteProBundle:Website:update.html.twig")
     * @Acl(
     *      id="oro_websitepro_create",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new Website());
    }

    /**
     * Edit website form
     *
     * @Route("/update/{id}", name="oro_websitepro_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_websitepro_update",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="EDIT"
     * )
     *
     * @param Website $website
     * @return array|RedirectResponse
     */
    public function updateAction(Website $website)
    {
        return $this->update($website);
    }

    /**
     * @param Website $website
     *
     * @return array|RedirectResponse
     */
    protected function update(Website $website)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $website,
            $this->createForm(WebsiteType::NAME, $website),
            function (Website $website) {
                return [
                    'route' => 'oro_websitepro_update',
                    'parameters' => ['id' => $website->getId()]
                ];
            },
            function (Website $website) {
                return [
                    'route' => 'oro_websitepro_view',
                    'parameters' => ['id' => $website->getId()]
                ];
            },
            $this->get('translator')->trans('oro.websitepro.controller.website.saved.message')
        );
    }
}
