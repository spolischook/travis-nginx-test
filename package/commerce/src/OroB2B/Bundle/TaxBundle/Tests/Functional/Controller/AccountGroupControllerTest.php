<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;

/**
 * @dbIsolation
 */
class AccountGroupControllerTest extends WebTestCase
{
    const ACCOUNT_GROUP_NAME = 'Account_Group';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes',
            ]
        );
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_group_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_account_group_type[name]' => self::ACCOUNT_GROUP_NAME,
                'orob2b_account_group_type[taxCode]' => $accountTaxCode->getId(),
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Account group has been saved', $html);
        $this->assertContains(self::ACCOUNT_GROUP_NAME, $html);
        $this->assertContains($accountTaxCode->getCode(), $html);

        /** @var AccountGroup $taxAccountGroup */
        $taxAccountGroup = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->findOneBy(['name' => self::ACCOUNT_GROUP_NAME]);
        $this->assertNotEmpty($taxAccountGroup);

        return $taxAccountGroup->getId();
    }

    /**
     * @param $id int
     * @depends testCreate
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_group_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $this->assertContains($accountTaxCode->getCode(), $html);
    }

    /**
     * @depends testView
     */
    public function testTaxCodeViewContainsEntity()
    {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_account_tax_code_view', ['id' => $accountTaxCode->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $grid = $crawler->filter('.inner-grid')->eq(1)->attr('data-page-component-options');
        $this->assertContains(self::ACCOUNT_GROUP_NAME, $grid);
    }

    /**
     * @depends testTaxCodeViewContainsEntity
     */
    public function testGrid()
    {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $response = $this->client->requestGrid(
            'account-groups-grid',
            ['account-groups-grid[_filter][name][value]' => self::ACCOUNT_GROUP_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertEquals($accountTaxCode->getCode(), $result['taxCode']);
    }
}
