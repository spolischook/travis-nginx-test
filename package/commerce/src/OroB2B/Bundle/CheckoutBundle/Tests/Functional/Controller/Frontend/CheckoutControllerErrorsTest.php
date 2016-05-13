<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class CheckoutControllerErrorsTest extends CheckoutControllerTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
            true
        );
        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
        ], true);
        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testStartCheckoutProductsWithoutPrices()
    {
        $translator = $this->getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->assertEquals($selectedAddressId, $this->getReference(self::DEFAULT_BILLING_ADDRESS)->getId());
        $noProductsError = $translator
            ->trans('orob2b.checkout.workflow.condition.order_line_item_has_count.message');
        $this->assertContains($noProductsError, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $this->client->request('POST', $form->getUri(), $data);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 500);
    }

    /**
     * @depends testStartCheckoutProductsWithoutPrices
     */
    public function testStartCheckoutSeveralProductsWithoutPrices()
    {
        $translator = $this->getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->assertEquals($selectedAddressId, $this->getReference(self::DEFAULT_BILLING_ADDRESS)->getId());
        $noProductsError = $translator
            ->trans('orob2b.checkout.order.line_items.order_line_item_has_without_prices.message');
        $this->assertContains($noProductsError, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        $this->assertContains($noProductsError, $crawler->html());

        foreach ($shoppingList->getLineItems() as $lineItem) {
            if ($lineItem->getProduct()->getSku() === LoadProductData::PRODUCT_5) {
                $shoppingList->removeLineItem($lineItem);
                break;
            }
        }
        $em->flush();
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $noProductsError = $translator
            ->trans('orob2b.checkout.workflow.condition.order_line_item_has_count.message');
        $this->assertContains($noProductsError, $crawler->html());
    }
}
