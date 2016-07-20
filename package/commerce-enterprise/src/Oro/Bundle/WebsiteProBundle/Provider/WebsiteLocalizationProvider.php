<?php

namespace Oro\Bundle\WebsiteProBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteLocalizationProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var LocalizationProvider */
    protected $provider;

    /**
     * @param ConfigManager $configManager
     * @param LocalizationProvider $provider
     */
    public function __construct(ConfigManager $configManager, LocalizationProvider $provider)
    {
        $this->configManager = $configManager;
        $this->provider = $provider;
    }

    /**
     * @param Website $website
     * @return array|Localization[]
     */
    public function getWebsiteLocalizations(Website $website)
    {
        $this->configManager->setScopeId($website->getId());

        $localizations = $this->provider->getLocalizations($this->getEnabledLocalizationIds());
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
