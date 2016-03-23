<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class AjaxEntityTotalsControllerTest extends WebTestCase
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

    public function testEntityTotalsActionForShoppingList()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $params = [
            'entityClassName' => ClassUtils::getClass($shoppingList),
            'entityId' => $shoppingList->getId()
        ];

        $this->client->request('GET', $this->getUrl('orob2b_pricing_entity_totals', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('total', $data);
        $this->assertEquals($data['total']['amount'], 282.43);
        $this->assertEquals($data['total']['currency'], 'EUR');

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertEquals($data['total']['amount'], 282.43);
        $this->assertEquals($data['total']['currency'], 'EUR');
    }
}
