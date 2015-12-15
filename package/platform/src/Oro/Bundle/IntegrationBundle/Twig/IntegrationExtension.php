<?php

namespace Oro\Bundle\IntegrationBundle\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;

use Twig_Extension;
use Twig_SimpleFunction;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;

class IntegrationExtension extends Twig_Extension
{
    const DEFAULT_THEME = 'OroIntegrationBundle:Form:fields.html.twig';

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('oro_integration_themes', [$this, 'getThemes']),
        ];
    }

    /**
     * @param FormView $view
     *
     * @return array
     */
    public function getThemes(FormView $view)
    {
        $themes = [static::DEFAULT_THEME];
        if (!$this->dispatcher->hasListeners(LoadIntegrationThemesEvent::NAME)) {
            return $themes;
        }

        $event = new LoadIntegrationThemesEvent($view, $themes);
        $this->dispatcher->dispatch(LoadIntegrationThemesEvent::NAME, $event);

        return $event->getThemes();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_integration';
    }
}
