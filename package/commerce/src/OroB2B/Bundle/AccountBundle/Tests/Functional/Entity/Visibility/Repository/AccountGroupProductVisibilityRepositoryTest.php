<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /** @var AccountGroupProductVisibilityRepository */
    protected $repository;

    /** @var  RegistryInterface */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility'
        );

        $this->loadFixtures(
            ['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']
        );
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility');

    }

    public function testGetCategoryByAccountGroupProductVisibility()
    {
        $accountGroupProductVisibility = $this->registry
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(['product' => $this->getReference(LoadProductData::PRODUCT_1)]);
        $accountGroupProductVisibility->setVisibility(AccountGroupProductVisibility::CATEGORY);
        $this->registry->getEntityManager()->remove($accountGroupProductVisibility);
        $this->registry->getEntityManager()->flush();
        $categories = $this->repository->getCategoryIdsByAccountGroupProductVisibility();
        $this->assertCount(1, $categories);
        $this->assertEquals($categories[0], $this->getReference('category_1_5_6_7')->getId());
    }

    public function testGetAccountGroupsForCategoryType()
    {
        $this->assertCount(1, $this->repository->getAccountGroupsWithCategoryVisibiliy());
    }

    /**
     * @return array
     */
    public function setToDefaultWithoutCategoryDataProvider()
    {
        return [
            [
                'category' => LoadCategoryData::FOURTH_LEVEL2,
                'deletedCategoryProducts' => ['product.8'],
            ],
        ];
    }
}
