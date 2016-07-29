<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_b2b_rfp.frontend_product_visibility';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Translator $translator
     */
    protected $translator;

    /**
     * @var ConfigManager $configManager
     */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
        ]);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        $availableInventoryStatuses = [
            $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
                ->find(Product::INVENTORY_STATUS_IN_STOCK)->getId()
        ];
        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, $availableInventoryStatuses);
        $this->configManager->flush();

        $this->translator = $this->getContainer()->get('translator');
    }

    public function testIndexAction()
    {
        $this->client->request('GET', $this->getUrl('orob2b_product_frontend_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotEmpty($content);
        $this->assertContains(LoadProductData::PRODUCT_1, $content);
        $this->assertContains(LoadProductData::PRODUCT_2, $content);
        $this->assertContains(LoadProductData::PRODUCT_3, $content);
    }

    public function testIndexDatagridViews()
    {
        // default view is DataGridThemeHelper::VIEW_GRID
        $response = $this->client->requestFrontendGrid('frontend-products-grid', [], true);
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayHasKey('shortDescription', $result['data'][0]);

        $response = $this->client->requestFrontendGrid(
            'frontend-products-grid',
            [
                'frontend-products-grid[row-view]' => DataGridThemeHelper::VIEW_LIST,
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayNotHasKey('image', $result['data'][0]);
        $this->assertArrayNotHasKey('shortDescription', $result['data'][0]);

        $response = $this->client->requestFrontendGrid(
            'frontend-products-grid',
            [
                'frontend-products-grid[row-view]' => DataGridThemeHelper::VIEW_GRID,
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayHasKey('shortDescription', $result['data'][0]);

        $response = $this->client->requestFrontendGrid(
            'frontend-products-grid',
            [
                'frontend-products-grid[row-view]' => DataGridThemeHelper::VIEW_TILES,
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayNotHasKey('shortDescription', $result['data'][0]);

        // view saves to session so current view is DataGridThemeHelper::VIEW_TILES
        $response = $this->client->requestFrontendGrid('frontend-products-grid', [], true);
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
        $this->assertArrayNotHasKey('shortDescription', $result['data'][0]);
    }

    public function testViewProductWithRequestQuoteAvailable()
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

        $this->assertContains(
            $this->translator->trans(
                'orob2b.frontend.sale.product.view.request_a_quote'
            ),
            $result->getContent()
        );
    }

    public function testViewProductWithoutRequestQuoteAvailable()
    {
        $product = $this->getProduct(LoadProductData::PRODUCT_3);

        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $product);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($product->getSku(), $result->getContent());
        $this->assertContains($product->getDefaultName()->getString(), $result->getContent());

        $this->assertNotContains(
            $this->translator->trans(
                'orob2b.frontend.sale.product.view.request_a_quote'
            ),
            $result->getContent()
        );
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
