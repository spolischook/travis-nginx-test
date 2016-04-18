<?php

namespace Oro\Component\Layout\Extension\Theme\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeDataProvider implements DataProviderInterface
{
    /** @var ThemeManager */
    protected $themeManager;

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        // TODO: it is expected that REST API for layout themes will be created
        // and this method will return URL of this API
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->themeManager->getTheme($context->get('theme'));
    }
}
