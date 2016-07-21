<?php

namespace OroCRMPro\Bundle\OutlookBundle\Api\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository;

/**
 * Adds additional configuration options to "outlook" section.
 * Adds Outlook related configuration sections if they are not added yet.
 */
class LoadConfigurationSections implements ProcessorInterface
{
    /** @var OutlookConfigurationRepository */
    protected $outlookConfigRepository;

    /** @var ConfigurationRepository */
    protected $configRepository;

    /**
     * @param OutlookConfigurationRepository $outlookConfigRepository
     * @param ConfigurationRepository        $configRepository
     */
    public function __construct(
        OutlookConfigurationRepository $outlookConfigRepository,
        ConfigurationRepository $configRepository
    ) {
        $this->outlookConfigRepository = $outlookConfigRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ListContext $context */

        if (!$context->hasResult()) {
            // Outlook related configuration can be added only in additional to existing options
            return;
        }

        /** @var ConfigurationSection[] $sections */
        $sections = $context->getResult();

        // add Outlook related configuration sections if they are not added yet
        $existingSectionIds = [];
        foreach ($sections as $key => $section) {
            $existingSectionIds[$section->getId()] = true;
        }
        $sectionIds = $this->configRepository->getSectionIds();
        foreach ($sectionIds as $sectionId) {
            if (!isset($existingSectionIds[$sectionId]) && $this->isOutlookConfiguration($sectionId)) {
                $sections[] = $this->configRepository->getSection(
                    $sectionId,
                    $context->get(GetScope::CONTEXT_PARAM)
                );
            }
        }

        // add additional configuration options to "outlook" section
        foreach ($sections as $key => $section) {
            $sectionId = $section->getId();
            if ('outlook' === $sectionId) {
                $section->setOptions(
                    array_merge(
                        $section->getOptions(),
                        $this->outlookConfigRepository->getOutlookSectionOptions(
                            $context->get(GetScope::CONTEXT_PARAM)
                        )
                    )
                );
                break;
            }
        }

        $context->setResult($sections);
    }

    /**
     * @param string $sectionId
     *
     * @return bool
     */
    protected function isOutlookConfiguration($sectionId)
    {
        return
            'outlook' === $sectionId
            || 0 === strpos($sectionId, 'outlook.');
    }
}
