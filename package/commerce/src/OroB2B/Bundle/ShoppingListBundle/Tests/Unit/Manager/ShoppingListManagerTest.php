<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ShoppingList */
    protected $shoppingListOne;
    /** @var  ShoppingList */
    protected $shoppingListTwo;
    /** @var  ShoppingListManager */
    protected $manager;
    /** @var  ShoppingList[] */
    protected $shoppingLists = [];
    /** @var LineItem[] */
    protected $lineItems = [];

    protected function setUp()
    {
        $this->shoppingListOne = $this->getEntity(1, true);
        $this->shoppingListTwo = $this->getEntity(2, false);

        $tokenStorage = $this->getTokenStorage(
            (new AccountUser())
                ->setFirstName('skip')
                ->setAccount(new Account())
        );

        $this->manager = new ShoppingListManager(
            $this->getManagerRegistry(),
            $tokenStorage,
            $this->getTranslator(),
            $this->getRoundingService()
        );
    }

    public function testCreateCurrent()
    {
        $this->manager->setCurrent(
            (new AccountUser())->setFirstName('setCurrent'),
            $this->shoppingListTwo
        );
        $this->assertTrue($this->shoppingListTwo->isCurrent());
        $this->assertFalse($this->shoppingListOne->isCurrent());
    }

    public function testSetCurrent()
    {
        $this->assertEmpty($this->shoppingLists);
        $this->manager->createCurrent();
        $this->assertCount(1, $this->shoppingLists);
        /** @var ShoppingList $list */
        $list = array_shift($this->shoppingLists);
        $this->assertTrue($list->isCurrent());
    }

    public function testAddLineItem()
    {
        $shoppingList = new ShoppingList();
        $lineItem = new LineItem();
        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
    }

    public function testAddLineItemDuplicate()
    {
        $shoppingList = new ShoppingList();
        $reflectionClass = new \ReflectionClass(get_class($shoppingList));
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($shoppingList, 1);

        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setQuantity(10);

        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        $this->assertEquals(1, count($this->lineItems));
        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(5);
        $this->manager->addLineItem($lineItemDuplicate, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        /** @var LineItem $resultingItem */
        $resultingItem = array_shift($this->lineItems);
        $this->assertEquals(15, $resultingItem->getQuantity());
    }

    public function testGetForCurrentUser()
    {
        $shoppingList = $this->manager->getForCurrentUser();
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
    }

    public function testBulkAddLineItems()
    {
        $shoppingList = new ShoppingList();
        $lineItems = [];
        for ($i = 0; $i < 10; $i++) {
            $lineItems[] = new LineItem();
        }

        $this->manager->bulkAddLineItems($lineItems, $shoppingList, 10);
        $this->assertEquals(10, $shoppingList->getLineItems()->count());
    }

    public function testGetShoppingLists()
    {
        $user = new AccountUser();

        $shoppingList1 = $this->getEntity(10, false);
        $shoppingList2 = $this->getEntity(20, false);
        $shoppingList3 = $this->getEntity(30, true);

        /* @var $repository ShoppingListRepository|\PHPUnit_Framework_MockObject_MockObject */
        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findAllExceptCurrentForAccountUser')
            ->with($user)
            ->willReturn([$shoppingList1, $shoppingList2]);
        $repository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->with($user)
            ->willReturn($shoppingList3);

        /* @var $entityManager EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        /* @var $registry ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $manager = new ShoppingListManager(
            $registry,
            $this->getTokenStorage($user),
            $this->getTranslator(),
            $this->getRoundingService()
        );

        $this->assertEquals(
            [
                'currentShoppingList' => $shoppingList3,
                'shoppingLists' => [$shoppingList1, $shoppingList2],
            ],
            $manager->getShoppingLists()
        );
    }

    /**
     * @param AccountUser $accountUser
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected function getTokenStorage(AccountUser $accountUser)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $securityToken */
        $securityToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $securityToken->expects($this->any())
            ->method('getUser')
            ->willReturn($accountUser);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($securityToken);

        return $tokenStorage;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService
     */
    protected function getRoundingService()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService $roundingService */
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService->expects($this->any())
            ->method('roundQuantity')
            ->will(
                $this->returnCallback(
                    function ($value, $unit, $product) {
                        return round($value, 0, PHP_ROUND_HALF_UP);
                    }
                )
            );

        return $roundingService;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListRepository->expects($this->any())
            ->method('findCurrentForAccountUser')
            ->willReturnCallback(function (AccountUser $accountUser) {
                if ($accountUser->getFirstName() === 'setCurrent') {
                    return $this->shoppingListOne;
                }

                return null;
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemRepository $lineItemRepository */
        $lineItemRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $lineItemRepository
            ->expects($this->any())
            ->method('findDuplicate')
            ->willReturnCallback(function (LineItem $lineItem) {
                /** @var ArrayCollection $shoppingListLineItems */
                $shoppingListLineItems = $lineItem->getShoppingList()->getLineItems();
                if ($lineItem->getShoppingList()->getId() === 1
                    && $shoppingListLineItems->count() > 0
                    && $shoppingListLineItems->current()->getUnit() === $lineItem->getUnit()
                ) {
                    return $lineItem->getShoppingList()->getLineItems()->current();
                }

                return null;
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['OroB2BShoppingListBundle:ShoppingList', $shoppingListRepository],
                ['OroB2BShoppingListBundle:LineItem', $lineItemRepository]
            ]));

        $entityManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(function ($obj) {
                if ($obj instanceof ShoppingList) {
                    $this->shoppingLists[] = $obj;
                }
                if ($obj instanceof LineItem) {
                    $this->lineItems[] = $obj;
                }
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        return $managerRegistry;
    }

    /**
     * @param int  $id
     * @param bool $isCurrent
     *
     * @return ShoppingList
     */
    protected function getEntity($id, $isCurrent)
    {
        $entity = (new ShoppingList())->setCurrent($isCurrent);
        $reflectionClass = new \ReflectionClass(get_class($entity));
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
