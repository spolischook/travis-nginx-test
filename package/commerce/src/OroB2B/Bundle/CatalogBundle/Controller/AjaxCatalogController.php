<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AjaxCatalogController extends Controller
{
    /**
     * @Route(
     *      "/category-move",
     *      name="orob2b_catalog_category_move"
     * )
     * @Method({"PUT"})
     * @AclAncestor("orob2b_catalog_category_update")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function categoryMoveAction(Request $request)
    {
        $nodeId = (int)$request->get('id');
        $parentId = (int)$request->get('parent');
        $position = (int)$request->get('position');

        return new JsonResponse(
            $this->get('orob2b_catalog.category_tree_handler')->moveNode($nodeId, $parentId, $position)
        );
    }
}
