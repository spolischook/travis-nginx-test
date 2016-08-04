<?php

namespace Oro\Bundle\WebsiteProBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteLocalizationProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * @param ConfigManager $configManager
     * @param LocalizationManager $localizationManager
     */
    public function __construct(ConfigManager $configManager, LocalizationManager $localizationManager)
    {
        $this->configManager = $configManager;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @param Website $website
     * @return array|Localization[]
     */
    public function getWebsiteLocalizations(Website $website)
    {
        $this->configManager->setScopeId($website->getId());

        $localizations = $this->localizationManager->getLocalizations($this->getEnabledLocalizationIds());
        $defaultLocalization = null;

        $defaultLocalizationId = $this->getDefaultLocalizationId();
        foreach ($localizations as $localization) {
            if ($localization->getId() == $defaultLocalizationId) {
                $defaultLocalization = $localization;
            }
        }

        $this->configManager->setScopeId(null);

        return [
            'default' => $defaultLocalization,
            'enabled' => $localizations
        ];
    }

    /**
     * @return array
     */
    protected function getEnabledLocalizationIds()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS));
    }

    /**
     * @return int
     */
    protected function getDefaultLocalizationId()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));
    }
}
