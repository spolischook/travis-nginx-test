<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Functional\Api;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailEntityControllerTest extends WebTestCase
{
    /** @var string */
    protected $baseUrl;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookEntitiesData',
                'OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookEmailEntitiesData'
            ]
        );
        $this->baseUrl = $this->getUrl('orocrmpro_api_outlook_get_entities');
    }

    public function testGetEntities()
    {
        // 2 - Contacts, 1 - Account
        $this->client->request('GET', $this->baseUrl . '?messageId=email1@orocrm-pro.func-test');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);

        // 1 - Lead
        $this->client->request('GET', $this->baseUrl . '?bcc=test4@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesSeveralFilters()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '?from=test1@example.com&to=test2@example.com&bcc=test4@example.com'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesToFilterShouldWorkForToCcBcc()
    {
        $this->client->request('GET', $this->baseUrl . '?to=test3@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        // 2 - Contacts, 1 - Account
        $this->client->request(
            'GET',
            $this->baseUrl . '?messageId=email1@orocrm-pro.func-test&page=2&limit=2',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetEntitiesNoFilters()
    {
        $this->client->request('GET', $this->baseUrl);
        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGetEntitiesMoreThanOneEmailFound()
    {
        $this->client->request('GET', $this->baseUrl . '?from=test1@example.com');
        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }
}
