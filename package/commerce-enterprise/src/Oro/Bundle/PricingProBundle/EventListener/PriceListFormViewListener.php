<?php

namespace Oro\Bundle\PricingProBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListFormViewListener
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
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onPriceListView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $priceListId = (int)$request->get('id');
        
        /** @var PriceList $priceList */
        $priceList = $this->doctrineHelper->getEntityReference('OroB2BPricingBundle:PriceList', $priceListId);

        $template = $event->getEnvironment()->render(
            'OroPricingProBundle:PriceList/partial:website_section.html.twig',
            [
                'entity' => $priceList
            ]
        );
        
        $this->addPriceListPricesBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addPriceListPricesBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.website.entity_plural_label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
