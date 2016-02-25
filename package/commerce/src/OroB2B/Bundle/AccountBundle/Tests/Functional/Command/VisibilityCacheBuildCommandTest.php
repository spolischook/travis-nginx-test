<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Command\VisibilityCacheBuildCommand;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;

/**
 * @dbIsolation
 */
class VisibilityCacheBuildCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);

        $this->loadFixtures([
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
        ]);
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $params
     * @param array $expectedMessages
     * @param array $expectedRecordsCount
     */
    public function testExecute(array $params, array $expectedMessages, array $expectedRecordsCount)
    {
        // Clear all resolved tables and check that all of them are empty
        $this->clearAllResolvedTables();

        $this->assertEquals(0, $this->getCategoryVisibilityResolvedCount());
        $this->assertEquals(0, $this->getAccountGroupCategoryVisibilityResolvedCount());
        $this->assertEquals(0, $this->getAccountCategoryVisibilityResolvedCount());

        $this->assertEquals(0, $this->getProductVisibilityResolvedCount());
        $this->assertEquals(0, $this->getAccountGroupProductVisibilityResolvedCount());
        $this->assertEquals(0, $this->getAccountProductVisibilityResolvedCount());

        // Run command and check result messages
        $result = $this->runCommand(VisibilityCacheBuildCommand::NAME, $params);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }

        // Check that all resolved tables are filled
        $this->assertEquals(
            $expectedRecordsCount['categoryVisibility'],
            $this->getCategoryVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['accountGroupCategoryVisibility'],
            $this->getAccountGroupCategoryVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['accountCategoryVisibility'],
            $this->getAccountCategoryVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['productVisibility'],
            $this->getProductVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['accountGroupProductVisibility'],
            $this->getAccountGroupProductVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['accountProductVisibility'],
            $this->getAccountProductVisibilityResolvedCount()
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'withoutParam' => [
                'params' => [],
                'expectedMessages' =>
                [
                    'Start the process of building the cache for all websites',
                    'The cache is updated successfully',
                ],
                'expectedCounts' => [
                    'categoryVisibility' => 8,
                    'accountGroupCategoryVisibility' => 14,
                    'accountCategoryVisibility' => 35,
                    'productVisibility' => 27,
                    'accountGroupProductVisibility' => 8,
                    'accountProductVisibility' => 5,
                ]
            ],
            'withExitsIdParam' => [
                'params' => ['--website_id=1'],
                'expectedMessages' =>
                [
                    'Start the process of building the cache for website "Default"',
                    'The cache is updated successfully',
                ],
                'expectedCounts' => [
                    'categoryVisibility' => 8,
                    'accountGroupCategoryVisibility' => 14,
                    'accountCategoryVisibility' => 35,
                    'productVisibility' => 3,
                    'accountGroupProductVisibility' => 6,
                    'accountProductVisibility' => 4,
                ]
            ],
            'withWrongIdParam' => [
                'params' => ['--website_id=0'],
                'expectedMessages' =>
                [
                    'Website id is not valid',
                ],
                'expectedCounts' => [
                    'categoryVisibility' => 0,
                    'accountGroupCategoryVisibility' => 0,
                    'accountCategoryVisibility' => 0,
                    'productVisibility' => 0,
                    'accountGroupProductVisibility' => 0,
                    'accountProductVisibility' => 0,
                ]
            ],
        ];
    }

    /**
     * @return int
     */
    protected function getCategoryVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getCategoryVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getAccountGroupCategoryVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getAccountGroupCategoryVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getAccountCategoryVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getAccountCategoryVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getProductVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getProductVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getAccountGroupProductVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getAccountGroupProductVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getAccountProductVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getAccountProductVisibilityResolvedRepository());
    }

    /**
     * @return EntityRepository
     */
    protected function getCategoryVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getAccountGroupCategoryVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getAccountCategoryVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getAccountGroupProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getAccountProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
    }

    protected function clearAllResolvedTables()
    {
        $this->deleteAllEntities($this->getCategoryVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getAccountGroupCategoryVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getAccountCategoryVisibilityResolvedRepository());

        $this->deleteAllEntities($this->getProductVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getAccountGroupProductVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getAccountProductVisibilityResolvedRepository());
    }

    /**
     * @param EntityRepository $repository
     * @return int
     */
    protected function getEntitiesCount(EntityRepository $repository)
    {
        return (int)$repository->createQueryBuilder('entity')
            ->select('count(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param EntityRepository $repository
     */
    protected function deleteAllEntities(EntityRepository $repository)
    {
        $repository->createQueryBuilder('entity')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
