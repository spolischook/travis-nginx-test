<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListRepositoryTest extends WebTestCase
{
    /** @var AccountUser */
    protected $accountUser;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );

        $this->accountUser = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    public function testFindCurrentForAccountUser()
    {
        $shoppingList = $this->getRepository()->findCurrentForAccountUser($this->accountUser);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertTrue($shoppingList->isCurrent());
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    public function findOneForAccountUser()
    {
        $shoppingList = $this->getRepository()->findOneForAccountUser($this->accountUser);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    public function findAvailableForAccountUser()
    {
        // Isset current shopping list
        $currentShoppingList = $this->getRepository()->findCurrentForAccountUser($this->accountUser);
        $availableShoppingList = $this->getRepository()->findAvailableForAccountUser($this->accountUser);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $availableShoppingList);
        $this->assertEquals($currentShoppingList->getId(), $availableShoppingList->getId());

        // Remove current shopping list
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $em->remove($currentShoppingList);
        $em->flush();

        $availableShoppingList = $this->getRepository()->findAvailableForAccountUser($this->accountUser);

        // Check shopping list is not current for account user
        $this->assertFalse($availableShoppingList->isCurrent());
        $this->assertEquals($this->accountUser, $availableShoppingList->getAccountUser());
    }

    public function testFindByUser()
    {
        $shoppingLists = $this->getRepository()->findByUser($this->accountUser, ['list.updatedAt' => 'asc']);
        $this->assertTrue(count($shoppingLists) > 0);
        /** @var ShoppingList $secondShoppingList */
        $shoppingList = array_shift($shoppingLists);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
        /** @var ShoppingList $secondShoppingList */
        $secondShoppingList = array_shift($shoppingLists);
        $this->assertTrue($shoppingList->getUpdatedAt() <= $secondShoppingList->getUpdatedAt());
    }

    public function testFindByUserAndId()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingListReference = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()
            ->findByUserAndId($this->accountUser, $shoppingListReference->getId());
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    public function testCreateFindForAccountUserQueryBuilder()
    {
        $repository = $this->getRepository();
        $account = $this->getAccountUser();

        $qb = $repository->createFindForAccountUserQueryBuilder($account);
        $this->assertInstanceOf('\Doctrine\ORM\QueryBuilder', $qb);

        /** @var ShoppingList[] $accountShoppingLists */
        $accountShoppingLists = $qb->getQuery()->execute();
        foreach ($accountShoppingLists as $shoppingList) {
            if ($shoppingList->getAccount() instanceof Account) {
                $this->assertEquals($account->getAccount()->getId(), $shoppingList->getAccount()->getId());
            } else {
                $this->assertEquals($account->getId(), $shoppingList->getAccountUser()->getId());
            }
        }
    }

    public function testFindAllExceptCurrentForAccountUser()
    {
        $repository = $this->getRepository();
        $account = $this->getAccountUser();
        $lists = $repository->findAllExceptCurrentForAccountUser($account);
        $this->assertGreaterThan(0, $lists);
        /** @var ShoppingList $list */
        foreach ($lists as $list) {
            $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $list);
            $this->assertFalse($list->isCurrent());
        }
    }

    public function testFindLatestForAccountUserExceptCurrent()
    {
        $repository = $this->getRepository();
        $account = $this->getAccountUser();
        $list = $repository->findLatestForAccountUserExceptCurrent($account);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $list);
        $this->assertFalse($list->isCurrent());
    }

    public function testFindOneByIdWithRelations()
    {
        $repository = $this->getRepository();
        /** @var ShoppingList $shoppingListReference */
        $expectedList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $actualList = $repository->findOneByIdWithRelations($expectedList->getId());
        $this->assertSame($expectedList, $actualList);
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BShoppingListBundle:ShoppingList');
    }
}
