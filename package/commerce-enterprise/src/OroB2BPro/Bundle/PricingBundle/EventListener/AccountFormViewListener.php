<?php

namespace OroB2BPro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountFormViewListener extends AbstractAccountFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
            'orob2b.pricing.fallback.current_account_only.label',
        PriceListAccountFallback::ACCOUNT_GROUP =>
            'orob2b.pricing.fallback.account_group.label',
    ];

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
        
        /** @var PriceListToAccount[] $priceLists */
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccount')
            ->findBy(['account' => $account], ['website' => 'ASC']);
        
        /** @var  PriceListAccountFallback[] $fallbackEntities */
        $fallbackEntities = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->findBy(['account' => $account]);
        
        $this->addPriceListInfo(
            $event,
            $priceLists,
            $fallbackEntities,
            $this->websiteProvider->getWebsites(),
            $this->fallbackChoices
        );
    }
}
