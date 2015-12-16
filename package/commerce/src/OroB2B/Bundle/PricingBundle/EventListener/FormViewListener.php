<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

class FormViewListener
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
     * @var FrontendPriceListRequestHandler
     */
    protected $frontendPriceListRequestHandler;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param FrontendPriceListRequestHandler $frontendPriceListRequestHandler
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        FrontendPriceListRequestHandler $frontendPriceListRequestHandler
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->frontendPriceListRequestHandler = $frontendPriceListRequestHandler;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var Account $account */
        $account = $this->doctrineHelper->getEntityReference('OroB2BAccountBundle:Account', (int)$request->get('id'));
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccount')
            ->findBy(['account' => $account], ['website' => 'ASC']);
        $this->addPriceListInfo($event, $priceLists);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountGroupView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->doctrineHelper->getEntityReference(
            'OroB2BAccountBundle:AccountGroup',
            (int)$request->get('id')
        );
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->findBy(['accountGroup' => $accountGroup], ['website' => 'ASC']);
        $this->addPriceListInfo($event, $priceLists);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Account:price_list_update.html.twig',
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
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);

        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Product:prices_view.html.twig',
            ['entity' => $product]
        );
        $this->addProductPricesBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onFrontendProductView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);
        $priceList = $this->frontendPriceListRequestHandler->getPriceList();

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:ProductPrice');

        $prices = $priceRepository->findByPriceListIdAndProductIds($priceList->getId(), [$product->getId()]);

        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Frontend/Product:productPrice.html.twig',
            ['prices' => $prices]
        );

        $scrollData = $event->getScrollData();
        $subBlockId = $scrollData->addSubBlock(0);
        $scrollData->addSubBlockData(0, $subBlockId, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Product:prices_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addProductPricesBlock($event->getScrollData(), $template);
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:PriceList');
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addProductPricesBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.pricing.productprice.entity_plural_label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param BasePriceListRelation[] $priceLists
     */
    protected function addPriceListInfo(BeforeListRenderEvent $event, $priceLists)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Account:price_list_view.html.twig',
            ['priceListsByWebsites' => $this->groupPriceListsByWebsite($priceLists)]
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
            $website = $priceList->getWebsite();
            $result[$website->getId()]['priceLists'][] = $priceList;
            $result[$website->getId()]['website'] = $website;
        }

        foreach ($result as &$websitePriceLists) {
            usort(
                $websitePriceLists['priceLists'],
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
}
