<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:ProductPrice');
    }

    /**
     * @dataProvider unitDataProvider
     * @param string $priceList
     * @param string $product
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductUnitsByPriceList($priceList, $product, $currency = null, array $expected = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Product $product */
        $product = $this->getReference($product);

        $units = $this->repository->getProductUnitsByPriceList($priceList, $product, $currency);
        $this->assertCount(count($expected), $units);
        foreach ($units as $unit) {
            $this->assertContains($unit->getCode(), $expected);
        }
    }

    /**
     * @return array
     */
    public function unitDataProvider()
    {
        return [
            [
                'price_list_1',
                'product.1',
                null,
                ['liter', 'bottle']
            ],
            [
                'price_list_1',
                'product.1',
                'EUR',
                ['bottle']
            ]
        ];
    }

    /**
     * @dataProvider getProductsUnitsByPriceListDataProvider
     * @param string $priceList
     * @param array $products
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductsUnitsByPriceList($priceList, array $products, $currency = null, array $expected = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $productsCollection = new ArrayCollection();

        foreach ($products as $productName) {
            /** @var Product $product */
            $product = $this->getReference($productName);
            $productsCollection->add($product);
        }

        $actual = $this->repository->getProductsUnitsByPriceList($priceList, $productsCollection, $currency);

        $expectedData = [];
        foreach ($expected as $productName => $units) {
            $product = $this->getReference($productName);
            $expectedData[$product->getId()] = $units;
        }

        $this->assertEquals($expectedData, $actual);
    }

    /**
     * @return array
     */
    public function getProductsUnitsByPriceListDataProvider()
    {
        return [
            [
                'priceList' => 'price_list_1',
                'products' => [
                    'product.1',
                    'product.2',
                    'product.3'
                ],
                'currency' => 'USD',
                'expected' => [
                    'product.1' => ['liter'],
                    'product.2' => ['liter'],
                    'product.3' => ['liter'],
                ]
            ],
            [
                'priceList' => 'price_list_1',
                'products' => [
                    'product.1',
                    'product.2',
                    'product.3'
                ],
                'currency' => 'EUR',
                'expected' => [
                    'product.1' => ['bottle'],
                    'product.2' => ['liter']
                ]
            ]
        ];
    }

    /**
     * @param string $productReference
     * @param array $priceReferences
     * @dataProvider getPricesByProductDataProvider
     */
    public function testGetPricesByProduct($productReference, array $priceReferences)
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);

        $expectedPrices = [];
        foreach ($priceReferences as $priceReference) {
            $expectedPrices[] = $this->getReference($priceReference);
        }

        $this->assertEquals(
            $this->getPriceIds($expectedPrices),
            $this->getPriceIds($this->repository->getPricesByProduct($product))
        );
    }

    /**
     * @return array
     */
    public function getPricesByProductDataProvider()
    {
        return [
            'first product' => [
                'productReference' => 'product.1',
                'priceReferences' => [
                    'product_price.10',
                    'product_price.2',
                    'product_price.7',
                    'product_price.1',
                    'product_price.6',
                ],
            ],
            'second product' => [
                'productReference' => 'product.2',
                'priceReferences' => [
                    'product_price.13',
                    'product_price.11',
                    'product_price.8',
                    'product_price.3',
                    'product_price.12',
                    'product_price.5',
                    'product_price.4'
                ],
            ],
        ];
    }

    /**
     * @param string|null $priceList
     * @param array $products
     * @param array $expectedPrices
     * @param bool $getTierPrices
     * @param string $currency
     * @param array $orderBy
     *
     * @dataProvider findByPriceListIdAndProductIdsDataProvider
     */
    public function testFindByPriceListIdAndProductIds(
        $priceList,
        array $products,
        array $expectedPrices,
        $getTierPrices = true,
        $currency = null,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        $priceListId = -1;
        if ($priceList) {
            /** @var PriceList $priceListEntity */
            $priceListEntity = $this->getReference($priceList);
            $priceListId = $priceListEntity->getId();
        }

        $productIds = [];
        foreach ($products as $product) {
            /** @var Product $productEntity */
            $productEntity = $this->getReference($product);
            $productIds[] = $productEntity->getId();
        }

        $expectedPriceIds = [];
        foreach ($expectedPrices as $price) {
            /** @var ProductPrice $priceEntity */
            $priceEntity = $this->getReference($price);
            $expectedPriceIds[] = $priceEntity->getId();
        }

        $actualPrices = $this->repository->findByPriceListIdAndProductIds(
            $priceListId,
            $productIds,
            $getTierPrices,
            $currency,
            null,
            $orderBy
        );

        $actualPriceIds = $this->getPriceIds($actualPrices);

        $this->assertEquals($expectedPriceIds, $actualPriceIds);
    }

    /**
     * @return array
     */
    public function findByPriceListIdAndProductIdsDataProvider()
    {
        return [
            'empty products' => [
                'priceList' => 'price_list_1',
                'products' => [],
                'expectedPrices' => [],
            ],
            'empty products without tier prices' => [
                'priceList' => 'price_list_1',
                'products' => [],
                'expectedPrices' => [],
            ],
            'not existing price list' => [
                'priceList' => null,
                'products' => ['product.1'],
                'expectedPrices' => [],
            ],
            'not existing price list without tier prices' => [
                'priceList' => null,
                'products' => ['product.1'],
                'expectedPrices' => [],
            ],
            'first valid set' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1'],
                'expectedPrices' => ['product_price.10', 'product_price.2', 'product_price.7', 'product_price.1'],
            ],
            'first valid set without tier prices' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1'],
                'expectedPrices' => ['product_price.10', 'product_price.7'],
                'getTierPrices' => false
            ],
            'first valid set without tier prices with currency' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1'],
                'expectedPrices' => ['product_price.10'],
                'getTierPrices' => false,
                'currency' => 'EUR'
            ],
            'second valid set' => [
                'priceList' => 'price_list_2',
                'products' => ['product.1', 'product.2'],
                'expectedPrices' => ['product_price.5', 'product_price.12', 'product_price.4', 'product_price.6'],
            ],
            'second valid set without tier prices' => [
                'priceList' => 'price_list_2',
                'products' => ['product.1', 'product.2'],
                'expectedPrices' => [],
                'getTierPrices' => false
            ],
            'second valid set with currency' => [
                'priceList' => 'price_list_2',
                'products' => ['product.1', 'product.2'],
                'expectedPrices' => ['product_price.5', 'product_price.4', 'product_price.6'],
                'getTierPrices' => true,
                'currency' => 'USD'
            ],
            'first valid set with order by currency, unit and quantity' => [
                'priceList' => 'price_list_2',
                'products' => ['product.1', 'product.2'],
                'expectedPrices' => ['product_price.5', 'product_price.4', 'product_price.6', 'product_price.12'],
                'getTierPrices' => true,
                'currency' => null,
                ['currency' => 'DESC', 'unit' => 'ASC', 'quantity' => 'ASC']
            ],
        ];
    }

    /**
     * @dataProvider getPricesBatchDataProvider
     *
     * @param string $priceList
     * @param array $products
     * @param array $productUnits
     * @param array $expectedPrices
     * @param array $currencies
     */
    public function testGetPricesBatch(
        $priceList,
        array $products,
        array $productUnits,
        array $expectedPrices,
        array $currencies = []
    ) {
        /** @var PriceList $priceListEntity */
        $priceListEntity = $this->getReference($priceList);
        $priceListId = $priceListEntity->getId();

        $productIds = [];
        foreach ($products as $product) {
            /** @var Product $productEntity */
            $productEntity = $this->getReference($product);
            $productIds[] = $productEntity->getId();
        }

        $productUnitCodes = [];
        foreach ($productUnits as $productUnit) {
            /** @var ProductUnit $productUnit */
            $productUnitEntity = $this->getReference($productUnit);
            $productUnitCodes[] = $productUnitEntity->getCode();
        }

        $expectedPriceData = [];
        foreach ($expectedPrices as $price) {
            /** @var ProductPrice $priceEntity */
            $priceEntity = $this->getReference($price);
            $expectedPriceData[] = [
                'id' => $priceEntity->getProduct()->getId(),
                'code' => $priceEntity->getUnit()->getCode(),
                'quantity' => $priceEntity->getQuantity(),
                'value' => $priceEntity->getPrice()->getValue(),
                'currency' => $priceEntity->getPrice()->getCurrency(),
            ];
        }
        $sorter = function ($a, $b) {
            if ($a['id'] === $b['id']) {
                return 0;
            }
            return ($a['id'] < $b['id']) ? -1 : 1;
        };

        $actualPrices = $this->repository->getPricesBatch($priceListId, $productIds, $productUnitCodes, $currencies);

        $expectedPriceData = usort($expectedPriceData, $sorter);
        $actualPrices = usort($actualPrices, $sorter);

        $this->assertEquals($expectedPriceData, $actualPrices);
    }

    /**
     * @return array
     */
    public function getPricesBatchDataProvider()
    {
        return [
            'empty' => [
                'priceList' => 'price_list_1',
                'products' => [],
                'productUnits' => [],
                'expectedPrices' => [],
            ],
            'first valid set' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1', 'product.2'],
                'productUnits' => ['product_unit.liter'],
                'expectedPrices' => [
                    'product_price.7',
                    'product_price.8',
                    'product_price.1',
                    'product_price.3',
                    'product_price.11'
                ],
            ],
            'first valid set with currency' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1', 'product.2'],
                'productUnits' => ['product_unit.liter'],
                'expectedPrices' => ['product_price.11'],
                'currencies' => ['EUR']
            ],
            'second valid set' => [
                'priceList' => 'price_list_2',
                'products' => ['product.2'],
                'productUnits' => ['product_unit.bottle'],
                'expectedPrices' => ['product_price.5', 'product_price.12'],
            ],
            'second valid set with currency' => [
                'priceList' => 'price_list_2',
                'products' => ['product.2'],
                'productUnits' => ['product_unit.bottle'],
                'expectedPrices' => ['product_price.5'],
                'currencies' => ['USD']
            ],
        ];
    }

    public function testDeleteByProductUnit()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');
        /** @var Product $notRemovedProduct */
        $notRemovedProduct = $this->getReference('product.2');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        /** @var ProductUnit $unit */
        $notRemovedUnit = $this->getReference('product_unit.bottle');

        $this->repository->deleteByProductUnit($product, $unit);

        $this->assertEmpty(
            $this->repository->findBy(
                [
                    'product' => $product,
                    'unit' => $unit
                ]
            )
        );

        $this->assertNotEmpty(
            $this->repository->findBy(
                [
                    'product' => $notRemovedProduct,
                    'unit' => $unit
                ]
            )
        );

        $this->assertNotEmpty(
            $this->repository->findBy(
                [
                    'product' => $product,
                    'unit' => $notRemovedUnit
                ]
            )
        );
    }

    public function testGetAvailableCurrencies()
    {
        $this->assertEquals(
            ['EUR' => 'EUR', 'USD' => 'USD'],
            $this->repository->getAvailableCurrencies()
        );

        $em = $this->getContainer()->get('doctrine')->getManager();

        $price = new Price();
        $price->setValue(1);
        $price->setCurrency('UAH');

        /** @var Product $product */
        $product = $this->getReference('product.1');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productPrice = new ProductPrice();
        $productPrice
            ->setPrice($price)
            ->setProduct($product)
            ->setQuantity(1)
            ->setUnit($unit)
            ->setPriceList($priceList);

        $em->persist($productPrice);
        $em->flush();

        $this->assertEquals(
            ['EUR' => 'EUR', 'UAH' => 'UAH', 'USD' => 'USD'],
            $this->repository->getAvailableCurrencies()
        );
    }

    public function testCountByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertCount(
            $this->repository->countByPriceList($priceList),
            $this->repository->findBy(['priceList' => $priceList->getId()])
        );
    }

    public function testDeleteByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->repository->deleteByPriceList($priceList);

        $this->assertEmpty($this->repository->findBy(['priceList' => $priceList->getId()]));

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference('price_list_2');
        $this->assertNotEmpty($this->repository->findBy(['priceList' => $priceList2->getId()]));

        $this->repository->deleteByPriceList($priceList2);
        $this->assertEmpty($this->repository->findBy(['priceList' => $priceList2->getId()]));
    }

    /**
     * @param ProductPrice[] $prices
     * @return array
     */
    protected function getPriceIds(array $prices)
    {
        $priceIds = [];
        foreach ($prices as $price) {
            $priceIds[] = $price->getId();
        }

        return $priceIds;
    }
}
