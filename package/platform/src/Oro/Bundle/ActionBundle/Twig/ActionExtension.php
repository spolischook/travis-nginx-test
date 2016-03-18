<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;

class ActionExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /** @var ActionManager */
    protected $manager;

    /** @var ApplicationsHelper */
    protected $appsHelper;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var OptionsHelper */
    protected $optionsHelper;

    /**
     * @param ActionManager $manager
     * @param ApplicationsHelper $appsHelper
     * @param ContextHelper $contextHelper
     * @param OptionsHelper $optionsHelper
     */
    public function __construct(
        ActionManager $manager,
        ApplicationsHelper $appsHelper,
        ContextHelper $contextHelper,
        OptionsHelper $optionsHelper
    ) {
        $this->manager = $manager;
        $this->appsHelper = $appsHelper;
        $this->contextHelper = $contextHelper;
        $this->optionsHelper = $optionsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'oro_action_widget_parameters',
                [$this->contextHelper, 'getActionParameters'],
                ['needs_context' => true]
            ),
            new \Twig_SimpleFunction('oro_action_widget_route', [$this->appsHelper, 'getWidgetRoute']),
            new \Twig_SimpleFunction('has_actions', [$this->manager, 'hasActions']),
            new \Twig_SimpleFunction('oro_action_frontend_options', [$this->optionsHelper, 'getFrontendOptions']),
        );
    }
}
