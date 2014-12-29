<?php

namespace OroPro\Bundle\OrganizationBundle\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\AbstractContentProvider;

class OrganizationSwitchContentProvider extends AbstractContentProvider
{

    /**
     * @var \Twig_Environment
     */
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
        return $this->twig->render('OroSecurityBundle:Organization:selector.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'organization_switch';
    }
}
