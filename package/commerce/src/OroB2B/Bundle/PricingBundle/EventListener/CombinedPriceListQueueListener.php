<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer;

class CombinedPriceListQueueListener
{
    /**
     * @var bool
     */
    protected $hasCollectionChanges = false;

    /**
     * @var bool
     */
    protected $hasProductPriceChanges = false;

    /**
     * @var CombinedPriceListQueueConsumer
     */
    protected $priceListQueueConsumer;

    /**
     * @var CombinedProductPriceQueueConsumer
     */
    protected $productPriceQueueConsumer;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var bool|null
     */
    protected $isRealTimeMode;

    /**
     * @param CombinedPriceListQueueConsumer $priceListQueueConsumer
     * @param CombinedProductPriceQueueConsumer $productPriceQueueConsumer
     * @param ConfigManager $configManager
     */
    public function __construct(
        CombinedPriceListQueueConsumer $priceListQueueConsumer,
        CombinedProductPriceQueueConsumer $productPriceQueueConsumer,
        ConfigManager $configManager
    ) {
        $this->priceListQueueConsumer = $priceListQueueConsumer;
        $this->productPriceQueueConsumer = $productPriceQueueConsumer;
        $this->configManager = $configManager;
    }

    public function onTerminate()
    {
        if ($this->hasCollectionChanges) {
            if ($this->isRealTimeMode()) {
                $this->priceListQueueConsumer->process();
            }
        }
        if ($this->hasProductPriceChanges) {
            if ($this->isRealTimeMode()) {
                $this->productPriceQueueConsumer->process();
            }
        }
    }

    public function onQueueChanged()
    {
        $this->hasCollectionChanges = true;
    }

    public function onProductPriceChanged()
    {
        $this->hasProductPriceChanges = true;
    }

    /**
     * @return bool
     */
    protected function isRealTimeMode()
    {
        if ($this->isRealTimeMode === null) {
            $key = OroB2BPricingExtension::ALIAS
                . ConfigManager::SECTION_MODEL_SEPARATOR
                . Configuration::PRICE_LISTS_UPDATE_MODE;
            $this->isRealTimeMode = $this->configManager->get($key) === CombinedPriceListQueueConsumer::MODE_REAL_TIME;
        }

        return $this->isRealTimeMode;
    }
}
