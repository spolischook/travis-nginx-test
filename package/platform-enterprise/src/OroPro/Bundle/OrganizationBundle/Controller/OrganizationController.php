<?php

namespace OroPro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oropro_organization_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @AclAncestor("oro_organization_view")
     * @Template()
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oropro_organization.entity.class')
        ];
    }

    /**
     * Create organization form
     *
     * @Route("/create", name="oropro_organization_create")
     * @Acl(
     *      id="oro_organization_create",
     *      type="entity",
     *      class="OroOrganizationBundle:Organization",
     *      permission="CREATE"
     * )
     *
     * @Template("OroProOrganizationBundle:Organization:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Organization());
    }

    /**
     * Edit organization form
     *
     * @Route("/update/{id}", name="oropro_organization_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @Acl(
     *      id="oro_organization_update",
     *      type="entity",
     *      class="OroOrganizationBundle:Organization",
     *      permission="EDIT"
     * )
     * @param Organization $entity
     * @return array
     */
    public function updateAction(Organization $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route("/view/{id}", name="oropro_organization_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_organization_view",
     *      type="entity",
     *      class="OroOrganizationBundle:Organization",
     *      permission="VIEW"
     * )
     * @param Organization $entity
     * @return array
     */
    public function viewAction(Organization $entity)
    {
        return [
            'entity' => $entity
        ];
    }

    /**
     * @param Organization $entity
     * @return array
     */
    protected function update(Organization $entity)
    {
        if ($this->get('oropro_organization.form.handler.organization')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oropro.organization.controller.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form'   => $this->get('oro_organization.form.organization')->createView(),
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="oro_organization_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_organization_view")
     * @param Organization $entity
     * @return array
     */
    public function infoAction(Organization $entity)
    {
        return ['entity' => $entity];
    }

    /**
     * @Route("/widget/users/{id}", name="oro_organization_widget_users", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_organization_view")
     * @param Organization $entity
     * @return array
     */
    public function usersAction(Organization $entity)
    {
        return ['entity' => $entity];
    }

    /**
     * Organization select for new entity creation process
     *
     * @Route("/selector", name="oropro_organization_selector_form")
     * @Template
     */
    public function organizationSelectAction()
    {
        $form = $this->createFormBuilder(null, ['csrf_protection' => false])
            ->add(
                '_sa_org_id',
                'oro_organization_choice_select2',
                [
                    'mapped'   => false,
                    'label'    => 'Organization',
                    'multiple' => false
                ]
            )
            ->setMethod('GET')
            ->getForm();
        return [
            'form'       => $form->createView(),
            'formAction' => $this->getRequest()->get('form_url')
        ];
    }
}
