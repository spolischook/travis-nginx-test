<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
        ]);
    }

    public function testIndexAction()
    {
        $this->client->request('GET', $this->getUrl('orob2b_product_frontend_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($this->getProduct(LoadProductData::PRODUCT_1)->getSku(), $result->getContent());
        $this->assertContains($this->getProduct(LoadProductData::PRODUCT_2)->getSku(), $result->getContent());
        $this->assertContains($this->getProduct(LoadProductData::PRODUCT_3)->getSku(), $result->getContent());
    }

    public function testIndexDatagridViews()
    {
        // default view is DataGridThemeHelper::VIEW_GRID
        $response = $this->requestFrontendGrid('frontend-products-grid');
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayHasKey('shortDescription', $result['data'][0]);

        $response = $this->requestFrontendGrid(
            'frontend-products-grid',
            [
                'frontend-products-grid[row-view]' => DataGridThemeHelper::VIEW_LIST,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayNotHasKey('image', $result['data'][0]);
        $this->assertArrayNotHasKey('shortDescription', $result['data'][0]);

        $response = $this->requestFrontendGrid(
            'frontend-products-grid',
            [
                'frontend-products-grid[row-view]' => DataGridThemeHelper::VIEW_GRID,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayHasKey('shortDescription', $result['data'][0]);

        $response = $this->requestFrontendGrid(
            'frontend-products-grid',
            [
                'frontend-products-grid[row-view]' => DataGridThemeHelper::VIEW_TILES,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayNotHasKey('shortDescription', $result['data'][0]);

        // view saves to session so current view is DataGridThemeHelper::VIEW_TILES
        $response = $this->requestFrontendGrid('frontend-products-grid');
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayNotHasKey('shortDescription', $result['data'][0]);
    }

    public function testViewProduct()
    {
        $product = $this->getProduct(LoadProductData::PRODUCT_1);

        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $product);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($product->getSku(), $result->getContent());
        $this->assertContains($product->getDefaultName()->getString(), $result->getContent());
    }

    /**
     * @param string $reference
     *
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }
}
