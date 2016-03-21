<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\FOSRestController;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestApiController extends FOSRestController
{
    /**
     * Get a list of entities
     *
     * @param Request $request
     *
     * @ApiDoc(description="Get entities", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetListContext $context */
        $context = $this->getContext($processor, $request);
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * Get an entity
     *
     * @param Request $request
     *
     * @ApiDoc(description="Get entity", resource=true, views={"rest_plain", "rest_json_api"})
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        $processor = $this->getProcessor($request);
        /** @var GetContext $context */
        $context = $this->getContext($processor, $request);
        $context->setId($request->attributes->get('id'));
        $context->setFilterValues(new RestFilterValueAccessor($request));

        $processor->process($context);

        return $this->buildResponse($context);
    }

    /**
     * @param Request $request
     *
     * @return ActionProcessorInterface
     */
    protected function getProcessor(Request $request)
    {
        /** @var ActionProcessorBagInterface $processorBag */
        $processorBag = $this->get('oro_api.action_processor_bag');

        return $processorBag->getProcessor($request->attributes->get('_action'));
    }

    /**
     * @param ActionProcessorInterface $processor
     * @param Request                  $request
     *
     * @return Context
     */
    protected function getContext(ActionProcessorInterface $processor, Request $request)
    {
        /** @var Context $context */
        $context = $processor->createContext();
        $context->getRequestType()->add(RequestType::REST);
        $context->setClassName($request->attributes->get('entity'));
        $context->setRequestHeaders(new RestRequestHeaders($request));

        return $context;
    }

    /**
     * @param Context $context
     *
     * @return Response
     */
    protected function buildResponse(Context $context)
    {
        $result = $context->getResult();

        $view = $this->view($result);
        $view->getSerializationContext()->setSerializeNull(true);

        $statusCode = $context->getResponseStatusCode();
        if (null !== $statusCode) {
            $view->setStatusCode($statusCode);
        }
        foreach ($context->getResponseHeaders()->toArray() as $key => $value) {
            $view->setHeader($key, $value);
        }

        return $this->handleView($view);
    }
}
