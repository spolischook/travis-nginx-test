<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Model\Address;
use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxationAddressProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider = new TaxationAddressProvider($this->settingsProvider);
    }

    protected function tearDown()
    {
        unset($this->settingsProvider, $this->addressProvider);
    }

    public function testGetOriginAddress()
    {
        $address = new Address();

        $this->settingsProvider
            ->expects($this->once())
            ->method('getOrigin')
            ->willReturn($address);

        $this->assertSame($address, $this->addressProvider->getOriginAddress());
    }

    /**
     * @dataProvider getAddressForTaxationProvider
     *
     * @param AbstractAddress|null $expectedResult
     * @param string $destination
     * @param Address $origin
     * @param bool $originByDefault
     * @param OrderAddress $billingAddress
     * @param OrderAddress $shippingAddress
     * @param array $exclusions
     */
    public function testGetAddressForTaxation(
        $expectedResult,
        $destination,
        $origin,
        $originByDefault,
        $billingAddress,
        $shippingAddress,
        $exclusions
    ) {
        $this->settingsProvider->expects($this->any())->method('getOrigin')->willReturn($origin);

        $this->settingsProvider
            ->expects($exclusions !== null ? $this->once() : $this->never())
            ->method('getBaseAddressExclusions')
            ->willReturn($exclusions);

        $this->settingsProvider
            ->expects($this->once())
            ->method('getDestination')
            ->willReturn($destination);

        $this->settingsProvider
            ->expects($this->once())
            ->method('isOriginBaseByDefaultAddressType')
            ->willReturn($originByDefault);

        $this->assertEquals(
            $expectedResult,
            $this->addressProvider->getAddressForTaxation($billingAddress, $shippingAddress)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAddressForTaxationProvider()
    {
        $countryUS = new Country('US');
        $countryCA = new Country('CA');

        $regionUSLA = new Region('US-LA');

        $originAddress = new Address();

        $billingAddress = new OrderAddress();
        $billingAddress->setCountry($countryUS);
        $billingAddress->setRegion($regionUSLA);

        $shippingAddress = new OrderAddress();
        $shippingAddress->setCountry($countryCA);

        $exclusions = [
            new TaxBaseExclusion(
                [
                    'country' => $countryUS,
                    'region' => $regionUSLA,
                    'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                ]
            ),
            new TaxBaseExclusion(
                [
                    'country' => $countryCA,
                    'region' => null,
                    'option' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                ]
            ),
        ];

        $usRegionTextAddress = (new OrderAddress())->setCountry($countryUS)->setRegionText('US LA');
        $usAlRegionAddress = (new OrderAddress())->setCountry($countryUS)->setRegion(new Region('AL'));

        return [
            'billing address' => [
                $billingAddress,
                TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                null,
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'shipping address' =>[
                $shippingAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                null,
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'null address' =>[
                null,
                null,
                null,
                null,
                $billingAddress,
                $shippingAddress,
                null
            ],
            'origin address by default' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                true,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'billing address with exclusion (use destination as base)' => [
                $billingAddress,
                TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                null,
                null,
                $billingAddress,
                $shippingAddress,
                $exclusions
            ],
            'shipping address with exclusion (use origin as base)' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                null,
                $billingAddress,
                $shippingAddress,
                $exclusions
            ],
            'shipping by default return origin if no billing and shipping' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                true,
                null,
                null,
                null,
            ],
            'shipping address with exclusion (use origin as base) region text do not match' => [
                $usRegionTextAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                null,
                $billingAddress,
                $usRegionTextAddress,
                [
                    new TaxBaseExclusion(
                        [
                            'country' => $countryUS,
                            'region' => $regionUSLA,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                        ]
                    ),
                    new TaxBaseExclusion(
                        [
                            'country' => $countryCA,
                            'region' => null,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                        ]
                    ),
                ]
            ],
            'shipping address with exclusion (use origin as base) region do not match' => [
                $usAlRegionAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                null,
                $billingAddress,
                $usAlRegionAddress,
                [
                    new TaxBaseExclusion(
                        [
                            'country' => $countryUS,
                            'region' => $regionUSLA,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                        ]
                    ),
                    new TaxBaseExclusion(
                        [
                            'country' => $countryCA,
                            'region' => null,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                        ]
                    ),
                ]
            ],
        ];
    }
}
