<?php

namespace OroCRMPro\Bundle\OutlookBundle\DependencyInjection;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroCRMProOutlookExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {

        $translator = new Translator($container, new MessageSelector());

        $configuration = new Configuration($translator);

        $config = $this->processConfiguration($configuration, $configs);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }
}
