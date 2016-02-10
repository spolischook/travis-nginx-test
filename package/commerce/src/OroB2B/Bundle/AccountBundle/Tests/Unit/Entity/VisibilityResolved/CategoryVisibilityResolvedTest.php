<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var CategoryVisibilityResolved */
    protected $categoryVisibilityResolved;

    /** @var Category */
    protected $category;

    protected function setUp()
    {
        $this->category = new Category();
        $this->categoryVisibilityResolved = new CategoryVisibilityResolved($this->category);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->entity, $this->website, $this->category);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $categoryVisibilityResolved = new CategoryVisibilityResolved(new Category());

        $this->assertPropertyAccessors(
            $categoryVisibilityResolved,
            [
                ['visibility', 0],
                ['sourceCategoryVisibility', new CategoryVisibility()],
                ['source', BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE],
            ]
        );
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->categoryVisibilityResolved->getCategory());
    }
}
