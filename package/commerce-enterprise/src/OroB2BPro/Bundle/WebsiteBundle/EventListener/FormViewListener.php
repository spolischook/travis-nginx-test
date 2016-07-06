<?php

namespace OroB2BPro\Bundle\WebsiteBundle\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class FormViewListener
{
    /**
     * @var string
     */
    protected $websiteLabel;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $registry
     */
    public function __construct(RequestStack $requestStack, ManagerRegistry $registry)
    {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
    }

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

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }
        
        $entityId = $request->get('id');
        $entity = $this->registry->getManagerForClass($this->dataClass)
            ->getRepository($this->dataClass)
            ->find($entityId);

        $template = $event->getEnvironment()->render(
            'OroB2BProWebsiteBundle::website_field.html.twig',
            ['label' => $this->websiteLabel, 'entity' => $entity]
        );
        $scrollData = $event->getScrollData();
        $scrollData->addSubBlockData(0, 0, $template);
    }

    /**
     * @param string $websiteLabel
     */
    public function setWebsiteLabel($websiteLabel)
    {
        $this->websiteLabel = $websiteLabel;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }
}
