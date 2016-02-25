<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\EventListener\PriceListSystemConfigSubscriber;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;

class PriceListSystemConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListConfigConverter $converter */
    protected $converterMock;

    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var  PriceListSystemConfigSubscriber */
    protected $subscriber;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    public function setUp()
    {
        /** @var PriceListConfigConverter|\PHPUnit_Framework_MockObject_MockObject $converterMock */
        $this->converterMock = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $this->registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new PriceListSystemConfigSubscriber(
            $this->converterMock,
            $this->eventDispatcher,
            $this->registry
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
            $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $em->expects($this->once())->method('flush');
            $this->registry->expects($this->once())->method('getManager')->willReturn($em);
            $this->eventDispatcher
                ->expects($this->once())
                ->method('dispatch')
                ->with(PriceListQueueChangeEvent::BEFORE_CHANGE, new PriceListQueueChangeEvent());
        } else {
            $this->registry->expects($this->never())->method('getManager');
            $this->eventDispatcher
                ->expects($this->never())
                ->method('dispatch');
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
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');
        $this->subscriber->updateAfter(new ConfigUpdateEvent([]));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                ConfigSettingsUpdateEvent::FORM_PRESET => 'formPreSet',
                ConfigSettingsUpdateEvent::BEFORE_SAVE => 'beforeSave',
                ConfigUpdateEvent::EVENT_NAME => 'updateAfter',
            ],
            $this->subscriber->getSubscribedEvents()
        );
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
