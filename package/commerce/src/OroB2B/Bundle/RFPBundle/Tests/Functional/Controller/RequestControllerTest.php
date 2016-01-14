<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_rfp_request_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContainsRequestData(
            LoadRequestData::FIRST_NAME,
            LoadRequestData::LAST_NAME,
            LoadRequestData::EMAIL,
            LoadRequestData::PO_NUMBER,
            $this->getFormatDate('Y-m-d'),
            $result->getContent()
        );
    }

    /**
     * @return integer
     */
    public function testView()
    {
        $response = $this->client->requestGrid(
            'rfp-requests-grid',
            [
                'rfp-requests-grid[_filter][firstName][value]' => LoadRequestData::FIRST_NAME,
                'rfp-requests-grid[_filter][lastName][value]' => LoadRequestData::LAST_NAME,
                'rfp-requests-grid[_filter][email][value]' => LoadRequestData::EMAIL,
                'rfp-requests-grid[_filter][poNumber][value]' => LoadRequestData::PO_NUMBER,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            sprintf('%s %s - Requests For Quote - Sales', LoadRequestData::FIRST_NAME, LoadRequestData::LAST_NAME),
            $result->getContent()
        );

        $this->assertContainsRequestData(
            LoadRequestData::FIRST_NAME,
            LoadRequestData::LAST_NAME,
            LoadRequestData::EMAIL,
            LoadRequestData::PO_NUMBER,
            $this->getFormatDate('M j, Y'),
            $result->getContent()
        );

        return $id;
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testInfo($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContainsRequestData(
            LoadRequestData::FIRST_NAME,
            LoadRequestData::LAST_NAME,
            LoadRequestData::EMAIL,
            LoadRequestData::PO_NUMBER,
            $this->getFormatDate('M j, Y'),
            $result->getContent()
        );
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testUpdate($id)
    {
        $updatedFirstName = LoadRequestData::FIRST_NAME . '_update';
        $updatedLastName = LoadRequestData::LAST_NAME . '_update';
        $updatedEmail = LoadRequestData::EMAIL . '_update';
        $updatedPoNumber = LoadRequestData::PO_NUMBER . '_update';

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('orob2b_rfp_request[requestProducts][0]');

        $form['orob2b_rfp_request[firstName]'] = $updatedFirstName;
        $form['orob2b_rfp_request[lastName]'] = $updatedLastName;
        $form['orob2b_rfp_request[email]'] = $updatedEmail;
        $form['orob2b_rfp_request[poNumber]'] = $updatedPoNumber;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request has been saved', $crawler->html());

        $this->assertContainsRequestData(
            $updatedFirstName,
            $updatedLastName,
            $updatedEmail,
            $updatedPoNumber,
            $this->getFormatDate('M j, Y'),
            $result->getContent()
        );
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $poNumber
     * @param string $date
     * @param string $html
     */
    protected function assertContainsRequestData($firstName, $lastName, $email, $poNumber, $date, $html)
    {
        $this->assertContains($firstName, $html);
        $this->assertContains($lastName, $html);
        $this->assertContains($email, $html);
        $this->assertContains($poNumber, $html);
        $this->assertContains($date, $html);
    }

    /**
     * @param string $format
     * @return string
     */
    private function getFormatDate($format)
    {
        $dateObj = new \DateTime('now', new \DateTimeZone('UTC'));
        $date = $dateObj->format($format);

        return $date;
    }
}
