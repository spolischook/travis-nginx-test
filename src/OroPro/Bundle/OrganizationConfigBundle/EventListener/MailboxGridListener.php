<?php

namespace OroPro\Bundle\OrganizationConfigBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class MailboxGridListener
{
    const ROUTE = 'oropro_organization_config';

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $source = $event->getDatagrid()->getDatasource();
        $request = $this->requestStack->getCurrentRequest();
        if (!$source instanceof OrmDatasource ||
            $request->attributes->get('_route') !== static::ROUTE
        ) {
            return;
        }

        $parameters = $event->getDatagrid()->getParameters();

        $parameters->set('organization_ids', [$request->attributes->get('_route_params')['id']]);
    }
}
