<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const SIDEBAR_ROUTE = 'orob2b_catalog_category_product_sidebar';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);
    }

    /**
     * @dataProvider viewDataProvider
     *
     * @param bool $includeSubcategories
     * @param array $expected
     */
    public function testView($includeSubcategories, $expected)
    {
        /** @var Category $secondLevelCategory */
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => $includeSubcategories,
            ]
        );
        $result = $this->getJsonResponseContent($response, 200);
        $count = count($expected);
        $this->assertCount($count, $result['data']);
        foreach ($result['data'] as $data) {
            $this->assertContains($data['sku'], $expected);
        }
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            'includeSubcategories' => [
                'includeSubcategories' => true,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ],
            ],
            'excludeSubcategories' => [
                'includeSubcategories' => false,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
        ];
    }

    public function testSidebarAction()
    {
        $categoryId = 2;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                static::SIDEBAR_ROUTE,
                [RequestProductHandler::CATEGORY_ID_KEY => $categoryId]
            ),
            ['_widgetContainer' => 'widget']
        );
        $json = $crawler->filterXPath('//*[@data-page-component-options]')->attr('data-page-component-options');
        $this->assertJson($json);
        $arr = json_decode($json, true);
        $this->assertEquals($arr['defaultCategoryId'], $categoryId);
        $this->assertCount(8, $arr['data']);
    }
}
