<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class FrontendProductPriceDatagridListenerTest extends AbstractProductPriceDatagridListenerTest
{
    use EntityTrait;

    /**
     * @var FrontendProductPriceDatagridListener
     */
    protected $listener;

    /**
     * @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $numberFormatter;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitLabelFormatter;

    /**
     * @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitValueFormatter;

    /**
     * @var UserCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyProvider;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    public function setUp()
    {
        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitLabelFormatter =
            $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
                ->disableOriginalConstructor()
                ->getMock();
        $this->unitValueFormatter =
            $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter')
                ->disableOriginalConstructor()
                ->getMock();
        $this->currencyProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * @return FrontendProductPriceDatagridListener
     */
    protected function createListener()
    {
        return new FrontendProductPriceDatagridListener(
            $this->translator,
            $this->priceListRequestHandler,
            $this->numberFormatter,
            $this->unitLabelFormatter,
            $this->unitValueFormatter,
            $this->currencyProvider,
            $this->registry
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function setUpPriceListRequestHandler($priceListId = null, array $priceCurrencies = [])
    {
        $this->priceListRequestHandler
            ->expects($this->any())
            ->method('getPriceListByAccount')
            ->willReturn($this->getPriceList($priceListId));

        $this->currencyProvider
            ->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(reset($priceCurrencies));
    }

    /**
     * @return array
     */
    public function onBuildBeforeDataProvider()
    {
        return [
            'no currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => [],
            ],
            'valid currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => ['EUR'],
                'expectedConfig' => [
                    'columns' => [
                        'minimum_price' => [
                            'label' => 'orob2b.pricing.productprice.price_in_EUR.trans',
                        ],
                    ],
                    'properties' => [
                        'prices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                    ],
                    'filters' => [
                        'columns' => [
                            'minimum_price' => [
                                'type' => 'frontend-product-price',
                                'data_name' => 'EUR'
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'minimum_price' => [
                                'data_name' => 'minimum_price',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                        ]
                    ],
                    'source' => [
                        'query' => [
                            'select' => [
                                'product_price.value as minimum_price'
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'product_price',
                                        'conditionType' => 'WITH',
                                        'condition' => 'product_price.product = product.id ' .
                                            'AND product_price.currency = \'EUR\' ' .
                                            'AND product_price.priceList = 1'
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @param string $priceCurrency
     * @param int|null $priceListId
     * @param array $sourceResults
     * @param array $combinedPrices
     * @param array $expectedResults
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(
        $priceCurrency,
        $priceListId = null,
        array $sourceResults = [],
        array $combinedPrices = [],
        array $expectedResults = []
    ) {
        $sourceResultRecords = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
        }

        $repository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('getPricesForProductsByPriceList')
            ->willReturn($combinedPrices);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BPricingBundle:CombinedProductPrice')
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BPricingBundle:CombinedProductPrice')
            ->willReturn($em);

        $this->setUpPriceListRequestHandler($priceListId, [$priceCurrency]);

        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(
                function ($price, $currency) {
                    return $currency . $price;
                }
            );

        $this->unitLabelFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(
                function ($unit) {
                    return $unit . '-formatted';
                }
            );

        $this->unitValueFormatter->expects($this->any())
            ->method('formatCode')
            ->willReturnCallback(
                function ($quantity, $unit) {
                    return $quantity . '-' . $unit . '-formatted';
                }
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new OrmResultAfter($datagrid, $sourceResultRecords);
        $this->listener->onResultAfter($event);
        $actualResults = $event->getRecords();

        $this->assertSameSize($expectedResults, $actualResults);
        foreach ($expectedResults as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                $this->assertEquals($value, $actualResult->getValue($name));
            }
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 2]);

        $price = new Price();
        $price->setCurrency('EUR');
        $price->setValue(20);

        $cpl1 = new CombinedProductPrice;
        $cpl1->setPrice($price);
        $cpl1->setProduct($product);
        $cpl1->setQuantity(1);
        $cpl1->setUnit((new ProductUnit())->setCode('item'));

        $price = new Price();
        $price->setCurrency('EUR');
        $price->setValue(21);

        $cpl2 = new CombinedProductPrice;
        $cpl2->setPrice($price);
        $cpl2->setProduct($product);
        $cpl2->setQuantity(2);
        $cpl2->setUnit((new ProductUnit())->setCode('item'));

        return [
            'no price list id' => [
                'priceCurrency' => 'USD',
            ],
            'with price list' => [
                'priceCurrency' => 'USD',
                'priceListId' => 1,
            ],
            'valid data' => [
                'priceCurrency' => 'EUR',
                'priceListId' => 1,
                'sourceResults' => [
                    [
                        'id' => 2
                    ],
                ],
                'combinedPrices' => [$cpl1, $cpl2],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'prices' => [
                            'item_1' => [
                                'price' => 20,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR20',
                                'unit' => 'item',
                                'formatted_unit' => 'item-formatted',
                                'quantity' => 1,
                                'quantity_with_unit' => '1-item-formatted',
                            ],
                            'item_2' => [
                                'price' => 21,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR21',
                                'unit' => 'item',
                                'formatted_unit' => 'item-formatted',
                                'quantity' => 2,
                                'quantity_with_unit' => '2-item-formatted',
                            ],
                        ],
                        'price_units' => null,
                        'price_quantities' => null,
                    ]
                ],
            ],
        ];
    }
}
