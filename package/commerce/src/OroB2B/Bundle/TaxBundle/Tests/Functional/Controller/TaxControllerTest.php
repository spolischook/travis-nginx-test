<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller;

use OroB2B\Bundle\TaxBundle\Entity\Tax;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class TaxControllerTest extends WebTestCase
{
    const TAX_CODE = 'unique';
    const TAX_CODE_UPDATED = 'uniqueUpdated';
    const TAX_DESCRIPTION = 'description';
    const TAX_DESCRIPTION_UPDATED = 'description updated';
    const TAX_RATE = 1;
    const TAX_RATE_UPDATED = 2;

    const TAX_SAVE_MESSAGE = 'Tax has been saved';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_tax_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('tax-taxes-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_tax_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxSave($crawler, self::TAX_CODE, self::TAX_DESCRIPTION, self::TAX_RATE);

        /** @var Tax $tax */
        $tax = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BTaxBundle:Tax')
            ->getRepository('OroB2BTaxBundle:Tax')
            ->findOneBy(['code' => self::TAX_CODE]);
        $this->assertNotEmpty($tax);

        return $tax->getId();
    }

    /**
     * @param $id int
     * @return int
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxSave($crawler, self::TAX_CODE_UPDATED, self::TAX_DESCRIPTION_UPDATED, self::TAX_RATE_UPDATED);

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
            $this->getUrl('orob2b_tax_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(self::TAX_CODE_UPDATED . ' - View - Taxes - Taxes', $html);

        $this->assertViewPage($html, self::TAX_CODE_UPDATED, self::TAX_DESCRIPTION_UPDATED, self::TAX_RATE_UPDATED);
    }

    /**
     * @param Crawler $crawler
     * @param string  $code
     * @param string  $description
     * @param string  $rate
     */
    protected function assertTaxSave(Crawler $crawler, $code, $description, $rate)
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_tax_type[code]' => $code,
                'orob2b_tax_type[description]' => $description,
                'orob2b_tax_type[rate]' => $rate,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(self::TAX_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description, $rate);
    }

    /**
     * @param string $html
     * @param string $code
     * @param string $description
     * @param string $rate
     */
    protected function assertViewPage($html, $code, $description, $rate)
    {
        $this->assertContains($code, $html);
        $this->assertContains($description, $html);
        $this->assertContains($rate . '%', $html);
    }
}
