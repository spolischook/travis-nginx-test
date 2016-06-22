<?php

namespace OroB2BPro\Bundle\WebsiteBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\Translation\TranslatorInterface;

class OrganizationViewListener
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onOrganizationView(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render('OroB2BProWebsiteBundle:Website:list.html.twig');
        $blockLabel = $this->translator->trans('orob2b.website.entity_plural_label');
        $blockId = $event->getScrollData()->addBlock($blockLabel);
        $subBlockId = $event->getScrollData()->addSubBlock($blockId);
        $event->getScrollData()->addSubBlockData($blockId, $subBlockId, $template);
    }
}
