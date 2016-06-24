<?php

namespace OroB2BPro\Bundle\WebsiteBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class FormViewListener
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BProWebsiteBundle::website_select.html.twig',
            ['form' => $event->getFormView()]
        );
        $scrollData = $event->getScrollData();
        $scrollData->addSubBlockData(0, 0, $template);
    }
}
