<?php

namespace OroPro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;

/**
 * @Route("/share")
 */
class ShareController extends Controller
{
    /**
     * @Route("/update/{entityClass}/{entityId}", name="oropro_share_update")
     *
     * @param string $entityClass
     * @param string $entityId
     *
     * @return array
     *
     * @Template("OroProSecurityBundle:Share:update.html.twig")
     */
    public function updateAction($entityClass, $entityId)
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $entity = $entityRoutingHelper->getEntity($entityClass, $entityId);
        if (!$this->get('oro_security.security_facade')->isGranted('SHARE', $entity)) {
            throw new AccessDeniedException();
        }

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oropro_share_update',
            $this->get('request_stack')->getCurrentRequest(),
            $entityRoutingHelper->getRouteParameters($entityClass, $entityId)
        );

        return $this->update($this->get('oropro_security.form.model.factory')->getShare(), $entity, $formAction);
    }

    /**
     * @Route("/entities/{entityClass}", name="oropro_share_with_dialog")
     *
     * @param string $entityClass
     *
     * @return array
     *
     * @Template("OroProSecurityBundle:Share:share_with.html.twig")
     */
    public function dialogAction($entityClass)
    {
        $supportedGridsInfo = $this->get('oropro_security.provider.share_grid_provider')
            ->getSupportedGridsInfo($entityClass);
        $gridEntityClass = '';
        if (isset($supportedGridsInfo[0]['className'])) {
            $gridEntityClass = $supportedGridsInfo[0]['className'];
        }

        return [
            'entityClass' => $gridEntityClass,
            'supportedGridsInfo' => $supportedGridsInfo,
            'params' => [
                'grid_path' => $this->generateUrl(
                    'oropro_share_with_datagrid',
                    ['entityClass' => $entityClass],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ],
        ];
    }

    /**
     * @Route("/entities/{entityClass}/grid", name="oropro_share_with_datagrid")
     *
     * @param string $entityClass
     *
     * @return array
     *
     * @Template("OroDataGridBundle:Grid:dialog/widget.html.twig")
     */
    public function datagridAction($entityClass)
    {
        return [
            'params' => [],
            'renderParams' => [],
            'multiselect' => true,
            'gridName' => $this->get('oropro_security.provider.share_grid_provider')->getGridName($entityClass),
        ];
    }

    /**
     * @param Share $model
     * @param object $entity
     * @param string $formAction
     *
     * @return array
     */
    protected function update(Share $model, $entity, $formAction)
    {
        $responseData = [
            'entity' => $entity,
            'model' => $model,
            'saved' => false
        ];

        if ($this->get('oropro_security.form.handler.share')->process($model, $entity)) {
            $responseData['saved'] = true;
        }
        $responseData['form'] = $this->get('oropro_security.form.share')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }
}
