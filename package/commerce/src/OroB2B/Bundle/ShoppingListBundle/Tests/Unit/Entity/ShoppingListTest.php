<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['label', 'label-test-123'],
            ['notes', 'notes-test-123'],
            ['currency', 'USD'],
            ['subtotal', 123.45],
            ['total', 543.21],
            ['organization', new Organization()],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $this->assertPropertyAccessors(new ShoppingList(), $properties);
        $this->assertPropertyCollections(new ShoppingList(), [
            ['lineItems', new LineItem()]
        ]);

        $label = 'label-test-775';
        $shoppingList = new ShoppingList();
        $shoppingList->setLabel($label);
        $this->assertEquals($label, $shoppingList);
    }

    public function testPrePersist()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->prePersist();
        $this->assertInstanceOf('\DateTime', $shoppingList->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $shoppingList->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->preUpdate();
        $this->assertInstanceOf('\DateTime', $shoppingList->getUpdatedAt());
    }
}
