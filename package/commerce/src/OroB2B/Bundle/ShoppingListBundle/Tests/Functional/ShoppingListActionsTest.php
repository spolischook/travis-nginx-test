<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListActionsTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
    }

    public function testCreateOrder()
    {
        if (!$this->client->getContainer()->hasParameter('orob2b_order.entity.order.class')) {
            $this->markTestSkipped('OrderBundle disabled');
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeOperation($shoppingList, 'orob2b_shoppinglist_createorder');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertStringStartsWith(
            $this->getUrl('orob2b_order_create', [ProductDataStorage::STORAGE_KEY => 1]),
            $data['redirectUrl']
        );

        $this->initClient([], $this->generateBasicAuthHeader());
        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $content = $crawler->filter('[data-ftid=orob2b_order_type_lineItems]')->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    public function testLineItemCreate()
    {
        /* @var $shoppingList ShoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /* @var $unit ProductUnit */
        $unit = $this->getReference('product_unit.bottle');
        /* @var $product2 Product */
        $product = $this->getReference('product.2');

        $crawler = $this->assertOperationForm(
            'orob2b_shoppinglist_addlineitem',
            $shoppingList->getId(),
            get_class($shoppingList)
        );

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_action_operation[lineItem][product]' => $product->getId(),
                'oro_action_operation[lineItem][quantity]' => 22.2,
                'oro_action_operation[lineItem][notes]' => 'test_notes',
                'oro_action_operation[lineItem][unit]' => $unit->getCode()
            ]
        );

        $this->assertOperationFormSubmitted($form, 'Line item has been added');
    }

    public function testLineItemCreateDuplicate()
    {
        /* @var $lineItem LineItem  */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $shoppingList = $lineItem->getShoppingList();

        $crawler = $this->assertOperationForm(
            'orob2b_shoppinglist_addlineitem',
            $shoppingList->getId(),
            get_class($shoppingList)
        );

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_action_operation[lineItem][product]' => $lineItem->getProduct()->getId(),
                'oro_action_operation[lineItem][quantity]' => 100,
                'oro_action_operation[lineItem][notes]' => 'test_notes',
                'oro_action_operation[lineItem][unit]' => $lineItem->getUnit()->getCode()
            ]
        );

        $this->assertOperationFormSubmitted($form, 'Line item has been added');
    }

    public function testLineItemUpdate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        $crawler = $this->assertOperationForm(
            'orob2b_shoppinglist_updatelineitem',
            $lineItem->getId(),
            get_class($lineItem)
        );

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_action_operation[lineItem][quantity]' => 33.3,
                'oro_action_operation[lineItem][unit]' => $unit->getCode(),
                'oro_action_operation[lineItem][notes]' => 'Updated test notes',
            ]
        );

        $this->assertOperationFormSubmitted($form, 'Line item has been updated');
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $operationName
     */
    protected function executeOperation(ShoppingList $shoppingList, $operationName)
    {
        $this->assertExecuteOperation(
            $operationName,
            $shoppingList->getId(),
            'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList',
            ['route' => 'orob2b_shopping_list_view']
        );
    }
}
