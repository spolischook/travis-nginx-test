<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class CheckoutTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['billingAddress', new OrderAddress()],
            ['saveBillingAddress', true],
            ['shipToBillingAddress', true],
            ['shippingAddress', new OrderAddress()],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['poNumber', 'PO-#1'],
            ['customerNotes', 'customer notes'],
            ['shipUntil', $now],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['website', new Website()],
            ['source', new CheckoutSource()]
        ];

        $entity = new Checkout();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testSetAccountUser()
    {
        $account = new Account();
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);
        $entity = new Checkout();
        $entity->setAccountUser($accountUser);
        $this->assertSame($account, $entity->getAccount());
    }

    /**
     * @dataProvider getLineItemsDataProvider
     * @param array $expected
     * @param string $sourceInterface
     */
    public function testGetLineItems(array $expected, $sourceInterface)
    {
        $entity = new Checkout();
        if ($sourceInterface) {
            $source = $this->getMockBuilder($sourceInterface)
                ->disableOriginalConstructor()
                ->getMock();
            $source
                ->expects($this->once())
                ->method('getLineItems')
                ->willReturn($expected);

            /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
            $checkoutSource = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource')
                ->disableOriginalConstructor()
                ->getMock();

            $checkoutSource
                ->expects($this->once())
                ->method('getEntity')
                ->willReturn($source);
            $entity->setSource($checkoutSource);
        }

        $this->assertSame($expected, $entity->getLineItems());
    }

    /**
     * @return array
     */
    public function getLineItemsDataProvider()
    {
        return [
            'without source' => [
                'expected' => [],
                'source' => null,
            ],
            'lineItemsAware' => [
                'expected' => [new \stdClass()],
                'source' => '\OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface',
            ],
            'LineItemsNotPricedAwareInterface' => [
                'expected' => [new \stdClass()],
                'source' => '\OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface',
            ]
        ];
    }
}
