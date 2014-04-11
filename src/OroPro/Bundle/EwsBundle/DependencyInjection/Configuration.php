<?php

namespace OroPro\Bundle\EwsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_pro_ews');
        $rootNode
            ->children()
            ->scalarNode('wsdl_endpoint')->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'version'     => ['value' => 'Exchange2010'],
                'login'       => ['value' => ''],
                'url'         => ['value' => ''],
                'password'    => ['value' => ''],
                'domain_list' => ['value' => [], 'type' => 'array'],
            ]
        );

        return $treeBuilder;
    }
}
