<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class OrderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['identifier', 'identifier-test-01'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['shippingAddress', new OrderAddress()],
            ['billingAddress', new OrderAddress()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['poNumber', 'PO-#1'],
            ['customerNotes', 'customer notes'],
            ['shipUntil', $now],
            ['currency', 'USD'],
            ['subtotal', 999.99],
            ['paymentTerm', new PaymentTerm()],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['priceList', new PriceList()]
        ];

        $order = new Order();
        $this->assertPropertyAccessors($order, $properties);
        $this->assertPropertyCollection($order, 'lineItems', new OrderLineItem());
    }

    public function testGetEmail()
    {
        $email = 'test@test.com';
        $order = new Order();
        $this->assertEmpty($order->getEmail());
        $accountUser = new AccountUser();
        $accountUser->setEmail($email);
        $order->setAccountUser($accountUser);
        $this->assertEquals($email, $order->getEmail());
    }

    public function testAccountUserToAccountRelation()
    {
        $order = new Order();

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $this->assertEmpty($order->getAccount());
        $order->setAccountUser($accountUser);
        $this->assertSame($account, $order->getAccount());
    }

    public function testPrePersist()
    {
        $order = new Order();
        $order->prePersist();
        $this->assertInstanceOf('\DateTime', $order->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Order();
        $order->preUpdate();
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }
}
