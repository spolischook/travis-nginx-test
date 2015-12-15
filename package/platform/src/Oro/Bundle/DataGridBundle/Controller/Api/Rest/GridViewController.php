<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @Rest\NamePrefix("oro_datagrid_api_rest_gridview_")
 */
class GridViewController extends RestController
{
    /**
     * @return Response
     * @Post("/gridviews")
     * @ApiDoc(
     *      description="Create grid view",
     *      resource=true,
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_create",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="CREATE"
     * )
     */
    public function postAction()
    {
        $this->checkCreatePublicAccess();

        return $this->handleCreateRequest();
    }

    /**
     * @param int $id
     *
     * @return Response
     * @Put("/gridviews/{id}", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Update grid view",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_update",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="EDIT"
     * )
     */
    public function putAction($id)
    {
        $gridView = $this->getManager()->find($id);
        if ($gridView->getType() === GridView::TYPE_PUBLIC) {
            $this->checkEditPublicAccess($gridView);
        } else {
            $this->checkCreatePublicAccess();
        }

        return $this->handleUpdateRequest($id);
    }

    /**
     * @param int $id
     *
     * @return Response
     * @Delete("/gridviews/{id}", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Delete grid view",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_delete",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="DELETE"
     * )
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @param GridView $gridView
     *
     * @throws AccessDeniedException
     */
    protected function checkEditPublicAccess(GridView $gridView)
    {
        if ($gridView->getType() !== GridView::TYPE_PUBLIC) {
            return;
        }

        if ($this->getSecurityFacade()->isGranted('oro_datagrid_gridview_update_public')) {
            return;
        }

        throw new AccessDeniedException();
    }

    /**
     * @throws AccessDeniedException
     */
    protected function checkCreatePublicAccess()
    {
        if ($this->getRequest()->request->get('type') !== GridView::TYPE_PUBLIC) {
            return;
        }

        if ($this->getSecurityFacade()->isGranted('oro_datagrid_gridview_publish')) {
            return;
        }

        throw new AccessDeniedException();
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity()
    {
        $entity = parent::createEntity();
        $entity->setOwner($this->getUser());

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->get('oro_datagrid.form.grid_view.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_datagrid.grid_view.form.handler.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_datagrid.grid_view.manager.api');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }
}
