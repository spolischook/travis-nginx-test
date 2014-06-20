<?php

namespace OroCRM\Bundle\ZendeskBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('orocrm_zendesk');

        SettingsBuilder::append(
            $rootNode,
            array(
                'zendesk_cron_schedule'      => array('type' => 'scalar', 'value' => '*/5 * * * *'),
                'api_email'                  => array('type' => 'scalar', 'value' => ''),
                'api_token'                  => array('type' => 'scalar', 'value' => ''),
                'zendesk_url'                => array('type' => 'scalar', 'value' => ''),
                'zendesk_default_user_email' => array('type' => 'scalar', 'value' => ''),
                'oro_default_username'       => array('type' => 'scalar', 'value' => ''),
            )
        );

        return $treeBuilder;
    }
}
