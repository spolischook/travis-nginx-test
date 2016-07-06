<?php

namespace OroB2BPro\Bundle\WebsiteBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class BusinessUnitViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onBusinessUnitView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $businessUnitId = (int)$request->get('id');
        if (!$businessUnitId) {
            return;
        }
        /** @var Product $product */
        $organization = $this->doctrineHelper->getEntityReference(
            'OroOrganizationBundle:BusinessUnit',
            $businessUnitId
        );
        if (!$organization) {
            return;
        }
        
        $template = $event->getEnvironment()->render(
            'OroB2BProWebsiteBundle:Website:list.html.twig',
            ['entity' => $organization]
        );
        $blockLabel = $this->translator->trans('orob2b.website.entity_plural_label');
        $blockId = $event->getScrollData()->addBlock($blockLabel);
        $subBlockId = $event->getScrollData()->addSubBlock($blockId);
        
        $event->getScrollData()->addSubBlockData($blockId, $subBlockId, $template);
    }
}
