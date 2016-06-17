<?php


namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Provider\CategoryProvider;

class CategoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var CategoryProvider */
    private $categoryProvider;

    protected function setUp()
    {
        $this->categoryProvider = new CategoryProvider();
    }

    public function testCategoryListFetchSuccess()
    {
        $this->assertEquals(
            [
                'account_management' => [
                    'label' => 'Account Management',
                    'tab' => true
                ],
                'marketing' => [
                    'label' => 'Marketing',
                    'tab' => true
                ],
                'sales_data' => [
                    'label' => 'Sales Data',
                    'tab' => true
                ],
                'address' => [
                    'label' => 'Address',
                    'tab' => false
                ],
                'calendar' => [
                    'label' => 'Calendar',
                    'tab' => false
                ]
            ],
            $this->categoryProvider->getList()
        );
    }
}
