<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /**
     * @param RequestStack $requestStack
     * @param ApplicationsHelper $applicationsHelper
     */
    public function __construct(RequestStack $requestStack, ApplicationsHelper $applicationsHelper)
    {
        $this->requestStack = $requestStack;
        $this->applicationsHelper = $applicationsHelper;
    }

    /**
     * @return string|null
     */
    public function getRequestRoute()
    {
        if (null === ($request = $this->requestStack->getMasterRequest())) {
            return null;
        }

        $route = $request->get('_route');

        return $route !== $this->applicationsHelper->getExecutionRoute() ? $route : null;
    }
}
