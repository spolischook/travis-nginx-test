<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListToWebsiteRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testFindByPrimaryKey()
    {
        $repository = $this->getRepository();

        /** @var PriceListToWebsite $actualPriceListToWebsite */
        $actualPriceListToWebsite = $repository->findOneBy([]);
        if (!$actualPriceListToWebsite) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedPriceListToWebsite = $repository->findByPrimaryKey(
            $actualPriceListToWebsite->getPriceList(),
            $actualPriceListToWebsite->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedPriceListToWebsite), spl_object_hash($actualPriceListToWebsite));
    }

    /**
     * @dataProvider getPriceListDataProvider
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($website, array $expectedPriceLists)
    {
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToWebsite = $this->getRepository()->getPriceLists($website);

        $actualPriceLists = array_map(
            function (PriceListToWebsite $priceListToWebsite) {
                return $priceListToWebsite->getPriceList()->getName();
            },
            $actualPriceListsToWebsite
        );

        $this->assertEquals($expectedPriceLists, $actualPriceLists);
    }

    /**
     * @return array
     */
    public function getPriceListDataProvider()
    {
        return [
            [
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList1'
                ]
            ],
            [
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList3'
                ]
            ],
        ];
    }

    /**
     * @return PriceListToWebsiteRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListToWebsite');
    }
}
