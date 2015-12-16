<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class AjaxPriceListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDefaultAction()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_default', ['id' => $priceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('successful', $data);
        $this->assertTrue($data['successful']);

        $defaultPriceLists = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceList')
            ->findBy(['default' => true]);

        $this->assertEquals([$priceList], $defaultPriceLists);
    }

    public function testGetPriceListCurrencyList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_currency_list', ['id' => $priceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertEquals($priceList->getCurrencies(), array_keys($data));
        $this->assertEquals(
            array_map(
                function ($currencyCode) {
                    return Intl::getCurrencyBundle()->getCurrencyName($currencyCode);
                },
                $priceList->getCurrencies()
            ),
            array_values($data)
        );
    }
}
