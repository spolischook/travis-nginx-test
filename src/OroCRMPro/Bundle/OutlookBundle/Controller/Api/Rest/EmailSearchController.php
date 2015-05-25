<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

use OroCRMPro\Bundle\OutlookBundle\Entity\Manager\EmailEntityApiEntityManager;

/**
 * @RouteResource("search")
 * @NamePrefix("orocrmpro_api_outlook_")
 */
class EmailSearchController extends RestGetController
{
    /**
     * Returns the list of entities by the given search string.
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *     name="search",
     *     requirements=".+",
     *     nullable=true,
     *     description="The search string."
     * )
     * @QueryParam(
     *      name="from",
     *      requirements=".+",
     *      nullable=true,
     *      description="The entity alias. One or several aliases separated by comma. Defaults to all entities"
     * )
     * @ApiDoc(
     *      description="Returns the list of entities by the given search string",
     *      resource=true
     * )
     *
     * @AclAncestor("oro_email_view")
     *
     * @return Response
     */
    public function getAction()
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);
        $stringToArrayFilter = new StringToArrayParameterFilter();
        $from  = $stringToArrayFilter->filter($this->getRequest()->get('from', null), null);

        $searchResults = $this->getSearchIndexer()->simpleSearch(
            $this->getRequest()->get('search'),
            ($page - 1) * $limit,
            $limit,
            $this->getSearchAliases($from)
        );

        $dispatcher = $this->get('event_dispatcher');
        foreach ($searchResults->getElements() as $item) {
            $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }
        $result = [];
        foreach ($searchResults as $resultRecord) {
            $originRecordData = $resultRecord->toArray();
            $resultRecordData = [
                'entity' => $originRecordData['entity_name'],
                'id'     => $originRecordData['record_id'],
                'title'  => $originRecordData['record_string'],
                'link'   => $originRecordData['record_url'],
            ];
            $result[] = $resultRecordData;
        }
        $view = $this->view($result, Codes::HTTP_OK);

        return $this->buildResponse(
            $view,
            RestApiReadInterface::ACTION_LIST,
            [
                'totalCount' => $searchResults->getRecordsCount()
            ]
        );
    }

    /**
     * Get search aliases for specified entity class(es). By default returns all associated entities.
     *
     * @param  array|null $from
     *
     * @return array
     */
    protected function getSearchAliases($from = null)
    {
        $entities = empty($from) ? $this->getManager()->getAssociations() : array_flip($from);
        $aliases  = array_intersect_key($this->getSearchIndexer()->getEntitiesListAliases(), $entities);

        return array_values($aliases);
    }

    /**
     * Get search indexer
     *
     * @return Indexer
     */
    public function getSearchIndexer()
    {
        return $this->get('oro_search.index');
    }

    /**
     * Get entity manager
     *
     * @return EmailEntityApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('orocrmpro_outlook.manager.api.email_entity');
    }
}
