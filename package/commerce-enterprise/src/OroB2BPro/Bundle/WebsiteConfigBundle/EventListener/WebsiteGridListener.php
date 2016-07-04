<?php

namespace OroB2BPro\Bundle\WebsiteConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class WebsiteGridListener
{
    /**
     * Adds config on website level to the website grid
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $config->offsetSetByPath(
            '[properties][config_link]',
            [
                'type'   => 'url',
                'route'  => 'orob2bpro_website_config',
                'params' => ['id']
            ]
        );
        $config->offsetSetByPath(
            '[actions][config]',
            [
                'type'         => 'navigate',
                'label'        => 'orob2b.website_config.grid.action.config',
                'link'         => 'config_link',
                'icon'         => 'cog',
                'acl_resource' => 'orob2bpro_website_update'
            ]
        );
    }
}
