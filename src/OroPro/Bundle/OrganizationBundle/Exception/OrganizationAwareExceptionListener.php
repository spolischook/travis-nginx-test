<?php

namespace OroPro\Bundle\OrganizationBundle\Exception;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class OrganizationAwareExceptionListener
{
    /** @var Router */
    protected $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Handles a kernel exception and returns redirect response in case of OrganizationAwareException
     * This happens when we work in System access mode and try to create new entity. In this case, user should
     * select organization for created record.
     *
     * @param GetResponseForExceptionEvent $event The exception event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof OrganizationAwareException) {
            $request = $event->getRequest();
            $parameters['form_url'] = $event->getRequest()->getUri();
            if ($request->get('_widgetContainer') === 'dialog') {
                $parameters['_widgetContainer'] = 'dialog';
            }
            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate(
                        'oropro_organization_selector_form',
                        $parameters
                    )
                )
            );
        }
    }
}
