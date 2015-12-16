<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

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
