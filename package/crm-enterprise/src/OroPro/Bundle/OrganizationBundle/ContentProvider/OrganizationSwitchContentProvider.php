<?php

namespace OroPro\Bundle\OrganizationBundle\ContentProvider;

use Oro\Bundle\UIBundle\Twig\PlaceholderExtension;
use Oro\Bundle\UIBundle\ContentProvider\AbstractContentProvider;

class OrganizationSwitchContentProvider extends AbstractContentProvider
{
    /** @var \Twig_Environment */
    protected $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        /** @var PlaceholderExtension $extension */
        $extension = $this->twig->getExtension(PlaceholderExtension::EXTENSION_NAME);

        return $extension->renderPlaceholder('organization_selector');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'organization_switch';
    }
}
