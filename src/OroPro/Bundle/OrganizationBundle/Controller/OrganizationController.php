<?php

namespace OroPro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     */
    public function indexAction(Request $request)
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
     */
    public function viewAction(Organization $entity)
    {
        return [
            'entity' => $entity,
//            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
//            // completely delete an owner and related entities or reassign related entities to another owner before
//            'allow_delete' => !$this->get('oro_organization.owner_deletion_manager')->hasAssignments($entity)
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

            return $this->get('oro_ui.router')->redirectAfterSave(
                ['route' => 'oropro_organization_update', 'parameters' => ['id' => $entity->getId()]],
                ['route' => 'oropro_organization_view', 'parameters' => ['id' => $entity->getId()]],
                $entity
            );
        }

        return [
            'entity' => $entity,
            'form' => $this->get('oropro_organization.form.organization')->createView(),
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="oro_organization_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_organization_view")
     */
    public function infoAction(Organization $entity)
    {
        return [
            'entity' => $entity
        ];
    }
}
