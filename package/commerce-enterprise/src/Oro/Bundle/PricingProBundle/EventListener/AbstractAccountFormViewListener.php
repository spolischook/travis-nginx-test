<?php

namespace Oro\Bundle\PricingProBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\PricingBundle\Entity\PriceListFallback;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractAccountFormViewListener
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
     * @var WebsiteProviderInterface
     */
    protected $websiteProvider;

    /**
     * @var string
     */
    protected $updateTemplate = 'OroB2BPricingBundle:Account:price_list_update.html.twig';

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteProviderInterface $websiteProvider
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        WebsiteProviderInterface $websiteProvider
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteProvider = $websiteProvider;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroPricingProBundle:Account:price_list_update.html.twig',
            ['form' => $event->getFormView()]
        );
        
        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param BasePriceListRelation[] $priceLists
     * @param array $fallbackEntities
     * @param Website[] $websites
     * @param array $choices
     */
    protected function addPriceListInfo(
        BeforeListRenderEvent $event,
        $priceLists,
        $fallbackEntities,
        $websites,
        $choices
    ) {
        $template = $event->getEnvironment()->render(
            'OroPricingProBundle:Account:price_list_view.html.twig',
            [
                'priceListsByWebsites' => $this->groupPriceListsByWebsite($priceLists),
                'fallbackByWebsites' => $this->groupFallbackByWebsites($fallbackEntities),
                'websites' => $websites,
                'choices' => $choices,
            ]
        );
        
        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }

    /**
     * @param BasePriceListRelation[] $priceLists
     * @return array
     */
    protected function groupPriceListsByWebsite(array $priceLists)
    {
        $result = [];
        foreach ($priceLists as $priceList) {
            $result[$priceList->getWebsite()->getId()][] = $priceList;
        }
        
        foreach ($result as &$websitePriceLists) {
            usort(
                $websitePriceLists,
                function (BasePriceListRelation $priceList1, BasePriceListRelation $priceList2) {
                    $priority1 = $priceList1->getPriority();
                    $priority2 = $priceList2->getPriority();
                    if ($priority1 == $priority2) {
                        return 0;
                    }
                    return ($priority1 < $priority2) ? -1 : 1;
                }
            );
        }
        
        return $result;
    }
    
    /**
     * @param PriceListFallback[] $entities
     * @return array
     */
    protected function groupFallbackByWebsites(array $entities)
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[$entity->getWebsite()->getId()] = $entity->getFallback();
        }
        
        return $result;
    }
}
