<?php

namespace Oro\Bundle\WebsiteProBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;
use Oro\Bundle\WebsiteProBundle\Provider\WebsiteLocalizationProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteLocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationProvider;

    /** @var WebsiteLocalizationProvider */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->localizationProvider = $this->getMockBuilder(LocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new WebsiteLocalizationProvider($this->configManager, $this->localizationProvider);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->configManager, $this->localizationProvider);
    }

    public function testGetWebsiteLocalizations()
    {
        $websiteId = 42;
        $ids = [100, 200];

        $defaultLocalization = $this->getLocalization(200);
        $enabledLocalizations = [$this->getLocalization(100), $defaultLocalization];

        $localizations = [
            'default' => $defaultLocalization,
            'enabled' => $enabledLocalizations
        ];

        $this->configManager->expects($this->at(0))->method('setScopeId')->with($websiteId);
        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_locale.' . Configuration::ENABLED_LOCALIZATIONS)
            ->willReturn($ids);
        $this->configManager->expects($this->at(2))
            ->method('get')
            ->with('oro_locale.' . Configuration::DEFAULT_LOCALIZATION)
            ->willReturn(200);
        $this->configManager->expects($this->at(3))->method('setScopeId')->with(null);

        $this->localizationProvider->expects($this->once())
            ->method('getLocalizations')
            ->with($ids)
            ->willReturn($enabledLocalizations);

        $this->assertEquals($localizations, $this->provider->getWebsiteLocalizations($this->getWebsite($websiteId)));
    }

    /**
     * @param int $id
     * @return Website
     */
    protected function getWebsite($id)
    {
        return $this->getEntity(Website::class, ['id' => $id]);
    }

    /**
     * @param int $id
     * @return Localization
     */
    protected function getLocalization($id)
    {
        return $this->getEntity(Localization::class, ['id' => $id]);
    }
}
