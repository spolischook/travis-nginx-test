<?php

namespace OroCRMPro\Bundle\OutlookBundle\Api\Processor\Get;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository;

/**
 * Adds additional configuration options to "outlook" section.
 */
class LoadConfigurationSection implements ProcessorInterface
{
    /** @var OutlookConfigurationRepository */
    protected $outlookConfigRepository;

    /**
     * @param OutlookConfigurationRepository $outlookConfigRepository
     */
    public function __construct(OutlookConfigurationRepository $outlookConfigRepository)
    {
        $this->outlookConfigRepository = $outlookConfigRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if (!$context->hasResult()) {
            // Outlook related configuration can be added only in additional to existing options
            return;
        }

        if ('outlook' === $context->getId()) {
            /** @var ConfigurationSection $section */
            $section = $context->getResult();
            $section->setOptions(
                array_merge(
                    $section->getOptions(),
                    $this->outlookConfigRepository->getOutlookSectionOptions(
                        $context->get(GetScope::CONTEXT_PARAM)
                    )
                )
            );
        }
    }
}
