<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData as ProductFixture;

/**
 * @dbIsolation
 */
class ProductRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']);
    }

    public function testFindOneBySku()
    {
        $this->assertNull($this->getRepository()->findOneBySku(uniqid('_fake_sku_', true)));

        $product = $this->getProduct(ProductFixture::PRODUCT_1);
        $expectedProduct = $this->getRepository()->findOneBySku(ProductFixture::PRODUCT_1);

        $this->assertEquals($product->getSku(), $expectedProduct->getSku());
    }

    /**
     * @dataProvider getSearchQueryBuilderDataProvider
     * @param string $search
     * @param int $firstResult
     * @param int $maxResult
     * @param array $expected
     */
    public function testGetSearchQueryBuilder($search, $firstResult, $maxResult, array $expected)
    {
        $queryBuilder = $this->getRepository()->getSearchQueryBuilder($search, $firstResult, $maxResult);
        $result = array_map(
            function ($product) {
                return $product['sku'];
            },
            $queryBuilder->getQuery()->getArrayResult()
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getSearchQueryBuilderDataProvider()
    {
        return [
            'product, 0, 10' => [
                'search' => 'duct',
                'firstResult' => 0,
                'maxResult' => 10,
                'expected' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.4',
                    'product.5',
                    'product.6',
                    'product.7',
                    'product.8',
                ],
            ],
            'product, 1, 1' => [
                'search' => 'oduct',
                'firstResult' => 1,
                'maxResult' => 1,
                'expected' => [
                    'product.2',
                ],
            ],
            'product, 0, 2' => [
                'search' => 'product',
                'firstResult' => 0,
                'maxResult' => 2,
                'expected' => [
                    'product.1',
                    'product.2',
                ],
            ],
        ];
    }

    /**
     * @dataProvider patternsAndSkuListProvider
     * @param string $pattern
     * @param array $expectedSkuList
     */
    public function testFindAllSkuByPattern($pattern, array $expectedSkuList)
    {
        $actualSkuList = $this->getRepository()->findAllSkuByPattern($pattern);

        $this->assertEquals($expectedSkuList, $actualSkuList);
    }

    /**
     * @return array
     */
    public function patternsAndSkuListProvider()
    {
        $allProducts = [
            ProductFixture::PRODUCT_1,
            ProductFixture::PRODUCT_2,
            ProductFixture::PRODUCT_3,
            ProductFixture::PRODUCT_4,
            ProductFixture::PRODUCT_5,
            ProductFixture::PRODUCT_6,
            ProductFixture::PRODUCT_7,
            ProductFixture::PRODUCT_8,
        ];

        return [
            'exact search 1' => [ProductFixture::PRODUCT_1, [ProductFixture::PRODUCT_1]],
            'exact search 2' => [ProductFixture::PRODUCT_2, [ProductFixture::PRODUCT_2]],
            'not found' => [uniqid('_fake_', true), []],
            'mask all products 1' => ['product.%', $allProducts],
            'mask all products 2' => ['pro%', $allProducts],
            'product suffixed with 1' => ['%.1', [ProductFixture::PRODUCT_1]],
            'product suffixed with 2' => ['%2', [ProductFixture::PRODUCT_2]],
        ];
    }

    public function testGetProductsQueryBuilder()
    {
        /** @var Product $product */
        $product = $this->getRepository()->findOneBy(['sku' => 'product.1']);
        $builder = $this->getRepository()->getProductsQueryBuilder([$product->getId()]);
        $result = $builder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertEquals($product, $result[0]);
    }

    /**
     * @param string $reference
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_product.product.class')
        );
    }

    public function testGetProductsIdsBySku()
    {
        $product1 = $this->getProduct(ProductFixture::PRODUCT_1);
        $product2 = $this->getProduct(ProductFixture::PRODUCT_2);
        $product3 = $this->getProduct(ProductFixture::PRODUCT_3);

        $this->assertEquals(
            [
                $product1->getSku() => $product1->getId(),
                $product2->getSku() => $product2->getId(),
                $product3->getSku() => $product3->getId(),
            ],
            $this->getRepository()->getProductsIdsBySku(
                [
                    $product3->getSku(),
                    $product1->getSku(),
                    $product2->getSku(),
                ]
            )
        );
    }

    public function testGetProductsNamesBySku()
    {
        $product1 = $this->getProduct(ProductFixture::PRODUCT_1);
        $product2 = $this->getProduct(ProductFixture::PRODUCT_2);
        $product3 = $this->getProduct(ProductFixture::PRODUCT_3);

        $this->assertEquals(
            [
                $product1,
                $product2,
                $product3,
            ],
            $this->getRepository()->getProductWithNamesBySku(
                [
                    $product3->getSku(),
                    $product1->getSku(),
                    $product2->getSku(),
                    'not a sku'
                ]
            )
        );

        $this->assertEmpty($this->getRepository()->getProductWithNamesBySku(['nonExistingSKU']));
    }

    public function testGetFilterSkuQueryBuilder()
    {
        /** @var Product $product */
        $product = $this->getRepository()->findOneBy(['sku' => 'product.1']);

        $builder = $this->getRepository()->getFilterSkuQueryBuilder([$product->getSku()]);
        $result = $builder->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($product->getSku(), $result[0]['sku']);
    }
}
