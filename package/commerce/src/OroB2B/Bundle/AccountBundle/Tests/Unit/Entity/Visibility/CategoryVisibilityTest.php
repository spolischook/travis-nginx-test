<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CategoryVisibility();
        $category = new Category();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', $category],
                ['visibility', CategoryVisibility::PARENT_CATEGORY],
            ]
        );
        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);
        $this->assertEquals(CategoryVisibility::CONFIG, $entity->getDefault($category));

        $entity->setCategory((new Category())->setParentCategory(new Category()));
        $this->assertEquals(
            CategoryVisibility::PARENT_CATEGORY,
            CategoryVisibility::getDefault($entity->getCategory())
        );
        $visibilityList = CategoryVisibility::getVisibilityList($category);
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
