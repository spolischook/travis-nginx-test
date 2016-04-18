<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class ProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    /**
     * @dataProvider setToDefaultWithoutCategoryDataProvider
     * @param $categoryName
     * @param array $expected
     */
    public function testSetToDefaultWithoutCategory($categoryName, array $expected)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);
        // setToDefaultWithoutCategory called in CategoryListener when removed category
        $this->deleteCategory($category);

        $actual = $this->getProductsByVisibilities();

        $this->assertSameSize($expected, $actual);
        foreach ($actual as $value) {
            $this->assertContains($value, $expected);
        }
    }

    /**
     * @return array
     */
    public function setToDefaultWithoutCategoryDataProvider()
    {
        return [
            'Delete FOURTH_LEVEL2' => [
                'categoryName' => LoadCategoryData::FOURTH_LEVEL2,
                'expected' => [
                    [
                        'product' => 'product.1',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],

                    [
                        'product' => 'product.2',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product.3',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product.4',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::HIDDEN
                    ],
                    [
                        'product' => 'product.5',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.7',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.8',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getProductsByVisibilities()
    {
        $website = $this->getDefaultWebsite();
        return array_map(
            function (ProductVisibility $visibility) {
                return [
                    'product' => $visibility->getProduct()->getSku(),
                    'website' => $visibility->getWebsite()->getName(),
                    'visibility' => $visibility->getVisibility()
                ];
            },
            $this->repository->findBy(['website' => $website])
        );
    }

    /**
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getDefaultWebsite();
    }
}
