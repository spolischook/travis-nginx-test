<?php

namespace OroCRM\Bundle\MailChimpBundle\Model\ExtendedMergeVar;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MailChimpBundle\Model\Segment\ColumnDefinitionListFactory;

class Provider implements CompositeProviderInterface
{
    /**
     * @var array|ProviderInterface[]
     */
    protected $providers;

    /**
     * @var ColumnDefinitionListFactory
     */
    protected $columnDefinitionListFactory;

    /**
     * @param ColumnDefinitionListFactory $columnDefinitionListFactory
     */
    public function __construct(ColumnDefinitionListFactory $columnDefinitionListFactory)
    {
        $this->providers = [];
        $this->columnDefinitionListFactory = $columnDefinitionListFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function addProvider(ProviderInterface $provider)
    {
        if (in_array($provider, $this->providers, true)) {
            return;
        }
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function provideExtendedMergeVars(MarketingList $marketingList)
    {
        $vars = $this
            ->columnDefinitionListFactory
            ->create($marketingList)
            ->getColumns();

        foreach ($this->providers as $provider) {
            $currentProviderVars = $provider->provideExtendedMergeVars($marketingList);
            if (!empty($currentProviderVars)) {
                $vars = array_merge($vars, $currentProviderVars);
            }
        }

        return $vars;
    }
}
