<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PricingBundle\EventListener\PriceListSystemConfigSubscriber;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

class PriceListSystemConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListConfigConverter
     */
    protected $converterMock;

    /**
     * @var PriceListChangeTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $changeTriggerHandler;

    /**
     * @var PriceListSystemConfigSubscriber
     */
    protected $subscriber;

    public function setUp()
    {
        $this->converterMock = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->changeTriggerHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new PriceListSystemConfigSubscriber(
            $this->converterMock,
            $this->changeTriggerHandler
        );
    }

    public function testFormPreSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getConfigManager();
        $settings = [
            'oro_b2b_pricing___default_price_lists' => [
                'value' => [[1, 100], [2, 200]],
            ],
        ];

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $convertedConfigs = $this->createConfigs(2);

        $this->converterMock->expects($this->once())
            ->method('convertFromSaved')
            ->with($settings['oro_b2b_pricing___default_price_lists']['value'])
            ->willReturn($convertedConfigs);

        $this->subscriber->formPreSet($event);

        $expected = [
            'oro_b2b_pricing___default_price_lists' => [
                'value' => $convertedConfigs,
            ],
        ];
        $this->assertEquals($expected, $event->getSettings());
    }

    public function testBeforeSave()
    {
        $values = $this->createConfigs(2);
        $settings = [
            'oro_b2b_pricing.default_price_lists' => [
                'value' => $values,
            ],
        ];
        $converted = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200],
        ];
        $expected = [
            'oro_b2b_pricing.default_price_lists' => [
                'value' => $converted,
            ],
        ];

        $configManager = $this->getConfigManager();

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);

        $this->converterMock->expects($this->once())
            ->method('convertBeforeSave')
            ->with($values)
            ->willReturn($converted);

        $this->subscriber->beforeSave($event);

        $this->assertEquals($expected, $event->getSettings());
    }

    /**
     * @dataProvider updateAfterDataProvider
     * @param array $changeSet
     * @param boolean $dispatch
     * @param string $key
     */
    public function testUpdateAfter($changeSet, $dispatch, $key)
    {
        $converted = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200],
        ];
        $values = $this->createConfigs(2);
        $configManager = $this->getConfigManager();

        $settings = [
            $key => [
                'value' => $values,
            ],
        ];
        $event = new ConfigSettingsUpdateEvent($configManager, $settings);

        $this->converterMock->expects($this->any())
            ->method('convertBeforeSave')
            ->with($values)
            ->willReturn($converted);

        $this->subscriber->beforeSave($event);
        if ($dispatch) {
            $this->changeTriggerHandler
                ->expects($this->once())
                ->method('handleConfigChange');
        } else {
            $this->changeTriggerHandler
                ->expects($this->never())
                ->method('handleConfigChange');
        }
        $this->subscriber->updateAfter(new ConfigUpdateEvent($changeSet));
    }

    /**
     * @return array
     */
    public function updateAfterDataProvider()
    {
        return [
            'changedAndApplicable' => [
                'changeSet' => ['some', 'changes'],
                'dispatch' => true,
                'key' => 'oro_b2b_pricing.default_price_lists',
            ],
            'notChangedAndApplicable' => [
                'changeSet' => [],
                'dispatch' => false,
                'key' => 'oro_b2b_pricing.default_price_lists',
            ],
            'changedAndNotApplicable' => [
                'changeSet' => ['some', 'changes'],
                'dispatch' => false,
                'key' => 'anotherKey',
            ],
        ];
    }

    public function testUpdateAfterWithNotApplicable()
    {
        $this->changeTriggerHandler
            ->expects($this->never())
            ->method('handleConfigChange');
        $this->subscriber->updateAfter(new ConfigUpdateEvent([]));
    }

    /**
     * @return ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigManager()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        return $configManager;
    }
}
