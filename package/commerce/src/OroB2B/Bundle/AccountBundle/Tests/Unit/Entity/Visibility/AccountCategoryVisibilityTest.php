<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new AccountCategoryVisibility();

        $category = new Category();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', $category],
                ['account', new Account()],
                ['visibility', AccountCategoryVisibility::CATEGORY],
            ]
        );

        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);

        $this->assertEquals(AccountCategoryVisibility::ACCOUNT_GROUP, $entity->getDefault($category));

        $visibilityList = AccountCategoryVisibility::getVisibilityList($category);
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
