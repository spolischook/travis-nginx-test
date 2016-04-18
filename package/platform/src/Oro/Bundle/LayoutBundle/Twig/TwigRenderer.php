<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Symfony\Bridge\Twig\Form\TwigRendererEngineInterface;
use Symfony\Bridge\Twig\Form\TwigRendererInterface;

use Oro\Component\Layout\Renderer;

/**
 * Heavily inspired by TwigRenderer class
 *
 * @see \Symfony\Bridge\Twig\Form\TwigRenderer
 */
class TwigRenderer extends Renderer implements TwigRendererInterface
{
    /**
     * @var TwigRendererEngineInterface
     */
    protected $engine;

    /**
     * @param TwigRendererEngineInterface $engine
     */
    public function __construct(TwigRendererEngineInterface $engine)
    {
        parent::__construct($engine);
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironment(\Twig_Environment $environment)
    {
        $this->engine->setEnvironment($environment);
    }
}
