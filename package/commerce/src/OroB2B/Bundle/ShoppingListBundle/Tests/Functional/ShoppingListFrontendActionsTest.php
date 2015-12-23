<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListFrontendActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        if (!$this->client->getContainer()->hasParameter('orob2b_order.entity.order.class')) {
            $this->markTestSkipped('OrderBundle disabled');
        }

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testCreateOrder()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeAction($shoppingList, 'orob2b_shoppinglist_frontend_action_createorder');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertStringStartsWith(
            $this->getUrl('orob2b_order_frontend_create', [ProductDataStorage::STORAGE_KEY => 1]),
            $data['redirectUrl']
        );

        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $content = $crawler->filter('[data-ftid=orob2b_order_frontend_type_lineItems]')->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    public function testCreateRequest()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeAction($shoppingList, 'orob2b_shoppinglist_frontend_action_request_quote');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertStringStartsWith(
            $this->getUrl('orob2b_rfp_frontend_request_create', [ProductDataStorage::STORAGE_KEY => 1]),
            $data['redirectUrl']
        );

        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $lineItems = $crawler->filter('[data-ftid=orob2b_rfp_frontend_request_requestProducts]');
        $this->assertNotEmpty($lineItems);
        $content = $lineItems->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $actionName
     */
    protected function executeAction(ShoppingList $shoppingList, $actionName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_api_frontend_action_execute_actions',
                [
                    'actionName' => $actionName,
                    'route' => 'orob2b_shopping_list_frontend_view',
                    'entityId' => $shoppingList->getId(),
                    'entityClass' => 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList'
                ]
            )
        );
    }
}
