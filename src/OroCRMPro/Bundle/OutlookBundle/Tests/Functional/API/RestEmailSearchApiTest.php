<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookEntitiesData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestContactApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookEntitiesData']);
    }

    /**
     * @return array
     */
    public function testEmailSearch()
    {
        $entityClasses = [];
        $baseUrl = $this->getUrl('orocrmpro_api_outlook_get_search');
        $this->client->request('GET', $baseUrl);

        // No search string - should return all entities:
        // 1 - User, 1 - Account, 4 - Contacts, 1 - Customer, 1 - Lead, 1 - Opportunity
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($entities);
        $this->assertCount(9, $entities);
        foreach ($entities as $entity) {
            if (!isset($entityClasses[$entity['entity']])) {
                $entityClasses[$entity['entity']] = $entity['entity'];
            }
        }
        // Check using multiple entities in from filter. Should return all entities.
        $this->client->request('GET', $baseUrl . sprintf('?from=%s', implode(',', $entityClasses)));
        $this->assertCount(count($entities), $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check pagination.
        $this->client->request('GET', $baseUrl . '?page=2&limit=5');
        $this->assertCount(4, $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check search by Contact name. Should return all related entities:
        // 1 - Account, 1 - Contact, 1 - Customer, 1 - Lead, 1 - Opportunity
        $this->client->request('GET', $baseUrl . sprintf('?search=%s', LoadOutlookEntitiesData::FIRST_CONTACT_NAME));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(5, $entities);
        $entity = reset($entities);
        $this->assertContains(LoadOutlookEntitiesData::FIRST_CONTACT_NAME, $entity['title']);

        // Check search by Contact name filtered by Contact entity only. Should return only one Contact entity.
        $this->client->request(
            'GET',
            $baseUrl . sprintf('?search=%s&from=%s', LoadOutlookEntitiesData::FIRST_CONTACT_NAME, $entity['entity'])
        );
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check filtering by Contact entity.
        $this->client->request('GET', $baseUrl . sprintf('?from=%s', $entity['entity']));
        $this->assertCount(4, $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check searching by non-existing entity title. Should return no results.
        $this->client->request('GET', $baseUrl . sprintf('?search=%s&page=1', 'NonExistentEntityTitle'));
        $this->assertEmpty($this->getJsonResponseContent($this->client->getResponse(), 200));
    }
}
