<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener\Config;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\TaxBundle\EventListener\Config\AddressEventListener;
use OroB2B\Bundle\TaxBundle\Factory\AddressModelFactory;
use OroB2B\Bundle\TaxBundle\Model\Address;

class AddressEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AddressEventListener */
    protected $listener;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|AddressModelFactory */
    protected $addressModelFactory;

    protected function setUp()
    {
        $this->addressModelFactory = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Factory\AddressModelFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AddressEventListener($this->addressModelFactory);
    }

    public function testFormPreSetWithoutKey()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')->willReturn([]);
        $this->addressModelFactory->expects($this->never())->method($this->anything());
        $event->expects($this->never())->method('setSettings');

        $this->listener->formPreSet($event);
    }

    public function testFormPreSet()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getSettings')
            ->willReturn(
                [
                    'orob2b_tax___origin_address' => [
                        'value' => [
                            'region_text' => 'Alabama',
                            'postal_code' => '35004',
                            'country' => 'US',
                            'region' => 'US-AL',
                        ],
                    ],
                ]
            );

        $address = (new Address(['region_text' => 'Alabama', 'postal_code' => '35004']))
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'));

        $this->addressModelFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($address);

        $event
            ->expects($this->once())
            ->method('setSettings')
            ->with(
                $this->callback(
                    function ($settings) use ($address) {
                        $this->assertInternalType('array', $settings);
                        $this->assertArrayHasKey('orob2b_tax___origin_address', $settings);
                        $this->assertInternalType('array', $settings['orob2b_tax___origin_address']);
                        $this->assertArrayHasKey('value', $settings['orob2b_tax___origin_address']);
                        $value = $settings['orob2b_tax___origin_address']['value'];
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Address', $value);
                        $this->assertEquals($address, $value);

                        return true;
                    }
                )
            );

        $this->listener->formPreSet($event);
    }

    public function testBeforeSaveWithoutKey()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')->willReturn([]);
        $this->addressModelFactory->expects($this->never())->method($this->anything());
        $event->expects($this->never())->method('setSettings');

        $this->listener->beforeSave($event);
    }

    public function testBeforeSaveNotModel()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')
            ->willReturn(['orob2b_tax.origin_address' => ['value' => null]]);

        $this->addressModelFactory->expects($this->never())->method($this->anything());
        $event->expects($this->never())->method('setSettings');

        $this->listener->beforeSave($event);
    }

    public function testBeforeSave()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $country = new Country('US');
        $region = new Region('US-AL');
        $address = new Address(['region_text' => 'Alabama', 'postal_code' => '35004']);
        $address->setCountry($country);
        $address->setRegion($region);

        $event->expects($this->once())->method('getSettings')
            ->willReturn(['orob2b_tax.origin_address' => ['value' => $address]]);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $event->expects($this->once())->method('setSettings')->with(
            $this->callback(
                function ($settings) {
                    $this->assertInternalType('array', $settings);
                    $this->assertArrayHasKey('orob2b_tax.origin_address', $settings);
                    $this->assertInternalType('array', $settings['orob2b_tax.origin_address']);
                    $this->assertArrayHasKey('value', $settings['orob2b_tax.origin_address']);
                    $this->assertInternalType('array', $settings['orob2b_tax.origin_address']['value']);
                    $this->assertArrayHasKey('country', $settings['orob2b_tax.origin_address']['value']);
                    $this->assertEquals('US', $settings['orob2b_tax.origin_address']['value']['country']);

                    $this->assertArrayHasKey('region', $settings['orob2b_tax.origin_address']['value']);
                    $this->assertEquals('US-AL', $settings['orob2b_tax.origin_address']['value']['region']);

                    $this->assertArrayHasKey('region_text', $settings['orob2b_tax.origin_address']['value']);
                    $this->assertEquals('Alabama', $settings['orob2b_tax.origin_address']['value']['region_text']);

                    $this->assertArrayHasKey('postal_code', $settings['orob2b_tax.origin_address']['value']);
                    $this->assertEquals('35004', $settings['orob2b_tax.origin_address']['value']['postal_code']);

                    return true;
                }
            )
        );

        $this->listener->beforeSave($event);
    }

    public function testBeforeSaveNoAddress()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $address ='some_value';

        $event->expects($this->once())->method('getSettings')
            ->willReturn(['orob2b_tax.origin_address' => ['value' => $address]]);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $event->expects($this->never())->method('setSettings');

        $this->listener->beforeSave($event);
    }
}
