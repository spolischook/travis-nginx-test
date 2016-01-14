<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductTaxCodeControllerTest extends WebTestCase
{
    const PRODUCT_TAX_CODE = 'unique';
    const PRODUCT_TAX_CODE_UPDATED = 'uniqueUpdated';
    const PRODUCT_TAX_CODE_DESCRIPTION = 'description';
    const PRODUCT_TAX_CODE_DESCRIPTION_UPDATED = 'description updated';
    const PRODUCT_TAX_CODE_SAVE_MESSAGE = 'Product Tax Code has been saved';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_tax_product_tax_code_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_tax_product_tax_code_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertProductTaxCodeSave($crawler, self::PRODUCT_TAX_CODE, self::PRODUCT_TAX_CODE_DESCRIPTION);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'tax-product-tax-codes-grid',
            ['tax-product-tax-codes-grid[_filter][code][value]' => self::PRODUCT_TAX_CODE]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_product_tax_code_update', ['id' => $result['id']])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertProductTaxCodeSave(
            $crawler,
            self::PRODUCT_TAX_CODE_UPDATED,
            self::PRODUCT_TAX_CODE_DESCRIPTION_UPDATED
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_product_tax_code_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(self::PRODUCT_TAX_CODE_UPDATED . ' - View - Product Tax Codes - Taxes', $html);

        $this->assertViewPage(
            $html,
            self::PRODUCT_TAX_CODE_UPDATED,
            self::PRODUCT_TAX_CODE_DESCRIPTION_UPDATED
        );
    }

    /**
     * @param Crawler $crawler
     * @param string  $code
     * @param string  $description
     */
    protected function assertProductTaxCodeSave(Crawler $crawler, $code, $description)
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_tax_product_tax_code_type[code]' => $code,
                'orob2b_tax_product_tax_code_type[description]' => $description,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(self::PRODUCT_TAX_CODE_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description);
    }

    /**
     * @param string $html
     * @param string $code
     * @param string $description
     */
    protected function assertViewPage($html, $code, $description)
    {
        $this->assertContains($code, $html);
        $this->assertContains($description, $html);
    }
}
