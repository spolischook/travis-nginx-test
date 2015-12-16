<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    /** @var string */
    protected $pricesByPriceListActionUrl = 'orob2b_pricing_price_by_pricelist';

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(),
                [
                    'HTTP_X-CSRF-Header' => 1,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
    }

    public function testUpdate()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_update_widget',
                [
                    'id' => $productPrice->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_pricing_price_list_product_price[quantity]' => 10,
                'orob2b_pricing_price_list_product_price[unit]' => $unit->getCode(),
                'orob2b_pricing_price_list_product_price[price][value]' => 20,
                'orob2b_pricing_price_list_product_price[price][currency]' => 'USD'
            ]
        );

        $this->assertSaved($form);
    }

    public function testUpdateDuplicateEntry()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        $productPriceEUR = $this->getReference('product_price.11');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_update_widget',
                [
                    'id' => $productPriceEUR->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form([
            'orob2b_pricing_price_list_product_price[quantity]' => $productPrice->getQuantity(),
            'orob2b_pricing_price_list_product_price[unit]' => $productPrice->getUnit()->getCode(),
            'orob2b_pricing_price_list_product_price[price][value]' => $productPrice->getPrice()->getValue(),
            'orob2b_pricing_price_list_product_price[price][currency]' => $productPrice->getPrice()->getCurrency(),
        ]);

        $this->assertSubmitError($form, 'orob2b.pricing.validators.product_price.unique_entity.message');
    }

    protected function assertSubmitError($form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*null/i', $html);
        $error = $this->getContainer()->get('translator')
            ->trans($message, [], 'validators');
        $this->assertContains($error, $html);
    }

    /**
     * @return array
     */
    public function getProductPricesByPriceListActionDataProvider()
    {
        return [
            'without currency' => [
                'product' => 'product.1',
                'priceList' => 'price_list_1',
                'expected' => [
                    'bottle' => [
                        ['price' => 12.2, 'currency' => 'EUR', 'qty' => 1],
                        ['price' => 12.2, 'currency' => 'EUR', 'qty' => 11],
                    ],
                    'liter' => [
                        ['price' => 10, 'currency' => 'USD', 'qty' => 1],
                        ['price' => 12.2, 'currency' => 'USD', 'qty' => 10],
                    ]
                ],
            ],
            'with currency' => [
                'product' => 'product.1',
                'priceList' => 'price_list_1',
                'expected' => [
                    'liter' => [
                        ['price' => 10.0000, 'currency' => 'USD', 'qty' => 1],
                        ['price' => 12.2000, 'currency' => 'USD', 'qty' => 10],
                    ]
                ],
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * @param Form $form
     */
    protected function assertSaved(Form $form)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*\d+/i', $html);
    }

    /**
     * @dataProvider getMatchingPriceActionDataProvider
     * @param string $product
     * @param string $priceList
     * @param float|int $qty
     * @param string $unit
     * @param string $currency
     * @param array $expected
     */
    public function testGetMatchingPriceAction($product, $priceList, $qty, $unit, $currency, array $expected)
    {
        /** @var Product $product */
        $product = $this->getReference($product);

        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $params = [
            'items' => [
                ['qty' => $qty, 'product' => $product->getId(), 'unit' => $unit, 'currency' => $currency]
            ],
            'pricelist' => $priceList->getId()
        ];

        $this->client->request('GET', $this->getUrl('orob2b_pricing_matching_price', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $expectedData = [];
        if (!empty($expected)) {
            $expectedData = [
                $product->getId() . '-' . $unit . '-' . $qty . '-' . $currency => $expected
            ];
        }

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function getMatchingPriceActionDataProvider()
    {
        return [
            [
                'product' => 'product.1',
                'priceList' => 'price_list_1',
                'qty' => 0.1,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => []
            ],
            [
                'product' => 'product.1',
                'priceList' => 'price_list_1',
                'qty' => 1,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 10,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product.1',
                'priceList' => 'price_list_1',
                'qty' => 10,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 12.2,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product.2',
                'priceList' => 'price_list_2',
                'qty' => 14,
                'unit' => 'bottle',
                'currency' => 'USD',
                'expected' => [
                    'value' => 12.2,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product.2',
                'priceList' => 'price_list_2',
                'qty' => 20,
                'unit' => 'bottle',
                'currency' => 'USD',
                'expected' => [
                    'value' => 12.2,
                    'currency' => 'USD'
                ]
            ]
        ];
    }

    /**
     * @dataProvider unitDataProvider
     * @param string $priceList
     * @param string $product
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductUnitsByCurrencyAction($priceList, $product, $currency = null, array $expected = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Product $product */
        $product = $this->getReference($product);

        $params = [
            'id' => $product->getId(),
            'price_list_id' => $priceList->getId(),
            'currency' => $currency
        ];

        $this->client->request('GET', $this->getUrl('orob2b_pricing_units_by_pricelist', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('units', $data);
        $this->assertEquals($expected, array_keys($data['units']));
    }
}
