<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class QuoteControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    public static $qid;

    /**
     * @var string
     */
    public static $qidUpdated;

    /**
     * @var string
     */
    public static $validUntil           = '2015-05-15T15:15:15+0000';

    /**
     * @var string
     */
    public static $validUntilUpdated    = '2016-06-16T16:16:16+0000';

    /**
     * @var string
     */
    public static $poNumber             = 'CA3333USD';

    /**
     * @var string
     */
    public static $poNumberUpdated      = 'CA5555USD';

    /**
     * @var string
     */
    public static $shipUntil            = '2015-09-15T00:00:00+0000';

    /**
     * @var string
     */
    public static $shipUntilUpdated     = '2015-09-20T00:00:00+0000';

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$qid          = 'TestQuoteID - ' . time() . '-' . rand();
        self::$qidUpdated   = self::$qid . ' - updated';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);
    }

    public function testCreate()
    {
        $crawler    = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_create'));
        $owner      = $this->getUser(LoadUserData::USER1);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('orob2b_sale_quote[quoteProducts][0]');
        $form['orob2b_sale_quote[owner]']      = $owner->getId();
        $form['orob2b_sale_quote[qid]']        = self::$qid;
        $form['orob2b_sale_quote[validUntil]'] = self::$validUntil;
        $form['orob2b_sale_quote[poNumber]']   = self::$poNumber;
        $form['orob2b_sale_quote[shipUntil]']  = self::$shipUntil;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Quote has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testIndex()
    {
        $crawler    = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_index'));
        $owner      = $this->getUser(LoadUserData::USER1);

        $result = $this->client->getResponse();

        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('quotes-grid', $crawler->html());

        $response = $this->client->requestGrid(
            'quotes-grid',
            ['quotes-grid[_filter][qid][value]' => self::$qid]
        );

        $result = static::getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        $row = reset($result['data']);

        $id = $row['id'];

        $this->assertEquals(self::$qid, $row['qid']);
        $this->assertEquals($owner->getFirstName() . ' ' . $owner->getLastName(), $row['ownerName']);
        $this->assertEquals(self::$validUntil, $row['validUntil']);
        $this->assertEquals(self::$poNumber, $row['poNumber']);
        $this->assertEquals(self::$shipUntil, $row['shipUntil']);

        return $id;
    }

    /**
     * @depends testIndex
     * @param int $id
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler    = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_update', ['id' => $id]));
        $owner      = $this->getUser(LoadUserData::USER2);

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('orob2b_sale_quote[quoteProducts][0]');
        $form['orob2b_sale_quote[owner]']      = $owner->getId();
        $form['orob2b_sale_quote[qid]']        = self::$qidUpdated;
        $form['orob2b_sale_quote[validUntil]'] = self::$validUntilUpdated;
        $form['orob2b_sale_quote[poNumber]']   = self::$poNumberUpdated;
        $form['orob2b_sale_quote[shipUntil]']  = self::$shipUntilUpdated;

        $form['orob2b_sale_quote[assignedUsers]'] = $this->getReference(LoadUserData::USER1)->getId();
        $form['orob2b_sale_quote[assignedAccountUsers]'] = implode(',', [
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getId(),
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getId()
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Quote has been saved', $crawler->html());

        $this->assertContains($this->getReference(LoadUserData::USER1)->getFullName(), $result->getContent());
        $this->assertContains($this->getReference(LoadUserData::ACCOUNT1_USER1)->getFullName(), $result->getContent());
        $this->assertContains($this->getReference(LoadUserData::ACCOUNT1_USER2)->getFullName(), $result->getContent());

        $this->client->request('GET', $this->getUrl('orob2b_sale_quote_index'));
        $response = $this->client->requestGrid(
            'quotes-grid',
            ['quotes-grid[_filter][id][value]' => $id]
        );

        $result = static::getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        $row = reset($result['data']);

        $this->assertEquals(self::$qidUpdated, $row['qid']);
        $this->assertEquals($owner->getFirstName() . ' ' . $owner->getLastName(), $row['ownerName']);
        $this->assertEquals(self::$validUntilUpdated, $row['validUntil']);
        $this->assertEquals(self::$poNumberUpdated, $row['poNumber']);
        $this->assertEquals(self::$shipUntilUpdated, $row['shipUntil']);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return int
     */
    public function testView($id)
    {
        $this->client->request('GET', $this->getUrl('orob2b_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        return $id;
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testLockedFieldAndBadge($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_view', ['id' => $id]));

        $this->assertContains('Not Locked', $crawler->html(), 'By default Quote shouldn\'t be locked');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_update', ['id' => $id]));

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_sale_quote[locked]'] = true;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Locked', $crawler->html());
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_sale_delete_quote', ['id' => $id]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        static::assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param array $submittedData
     * @param array $expectedData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, array $expectedData)
    {
        $this->prepareProviderData($submittedData);

        $crawler    = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_create'));

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('orob2b_sale_quote[quoteProducts][0]');
        foreach ($submittedData as $field => $value) {
            $form[QuoteType::NAME . $field] = $value;
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $filtered = $crawler->filter($expectedData['filter']);

        $this->assertEquals(1, $filtered->count());
        $this->assertContains($expectedData['contains'], $filtered->html());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'invalid owner' => [
                'submittedData' => [
                    '[owner]' => 333,
                ],
                'expectedData'  => [
                    'contains' => 'This value is not valid',
                    'filter' => '.validation-failed',
                ],
            ],
            'valid owner' => [
                'submittedData' => [
                    '[owner]' => function () {
                        return $this->getUser(LoadUserData::USER1)->getId();
                    },
                ],
                'expectedData'  => [
                    'contains'  => 'Quote has been saved',
                    'filter'    => 'body',
                ],
            ],
        ];
    }

    /**
     * @param array &$data
     */
    protected function prepareProviderData(array &$data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \Closure) {
                $data[$key] = $value();
            }
        }
    }

    /**
     * @param string $username
     * @return User
     */
    protected function getUser($username)
    {
        return $this->getReference($username);
    }
}
