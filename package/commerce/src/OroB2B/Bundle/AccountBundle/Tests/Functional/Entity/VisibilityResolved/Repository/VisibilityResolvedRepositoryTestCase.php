<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AbstractVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository\ResolvedEntityRepositoryTestTrait;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class VisibilityResolvedRepositoryTestCase extends WebTestCase
{
    use ResolvedEntityRepositoryTestTrait;

    /** @var  Registry */
    protected $registry;
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->registry->getManager();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    protected function tearDown()
    {
        $this->registry->getManager()->clear();
        parent::tearDown();
    }

    /**
     * @dataProvider clearTableDataProvider
     * @param integer $expectedRows
     */
    public function testClearTable($expectedRows)
    {
        $countQuery = $this->getRepository()
            ->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery();

        $this->assertEquals($expectedRows, $countQuery->getSingleScalarResult());
        $deletedCount = $this->getRepository()->clearTable();

        $this->assertEquals(0, $countQuery->getSingleScalarResult());
        $this->assertEquals($expectedRows, $deletedCount);
    }

    /**
     * @dataProvider insertByCategoryDataProvider
     *
     * @param string $websiteReference
     * @param string $targetEntityReference
     * @param string $visibility
     * @param array $expectedData
     */
    public function testInsertByCategory($websiteReference, $targetEntityReference, $visibility, array $expectedData)
    {
        $targetEntity = $this->getReference($targetEntityReference);
        $this->getRepository()->clearTable();
        $website = $websiteReference ? $this->getReference($websiteReference) : null;
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $website
        );
        $resolvedEntities = $this->getResolvedValues();
        $this->assertCount(count($expectedData), $resolvedEntities);
        foreach ($expectedData as $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);
            /** @var Website $website */
            $website = $this->getReference($data['website']);
            $resolvedVisibility = $this->getResolvedVisibility($resolvedEntities, $product, $targetEntity, $website);
            $this->assertEquals($this->getCategory($product)->getId(), $resolvedVisibility->getCategory()->getId());
            $this->assertEquals($visibility, $resolvedVisibility->getVisibility());
        }
    }

    /**
     * @dataProvider insertStaticDataProvider
     */
    public function testInsertStatic($expectedRows)
    {
        $this->getRepository()->clearTable();
        $this->getRepository()->insertStatic($this->getInsertFromSelectExecutor());
        $resolved = $this->getResolvedValues();
        $this->assertCount($expectedRows, $resolved);
        $visibilities = $this->getSourceRepository()->findAll();
        foreach ($resolved as $resolvedValue) {
            $source = $this->getSourceVisibilityByResolved(
                $visibilities,
                $resolvedValue
            );
            $this->assertNotNull($source);
            if ($resolvedValue->getVisibility() == BaseProductVisibilityResolved::VISIBILITY_HIDDEN) {
                $visibility = VisibilityInterface::HIDDEN;
            } elseif ($resolvedValue->getVisibility() == AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL) {
                $visibility = AccountProductVisibility::CURRENT_PRODUCT;
            } else {
                $visibility = VisibilityInterface::VISIBLE;
            }
            $this->assertEquals(
                $source->getVisibility(),
                $visibility
            );
        }
    }

    public function testFindByPrimaryKey()
    {
        /** @var BaseProductVisibilityResolved $actualEntity */
        $actualEntity = $this->getRepository()->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $this->assertEquals(spl_object_hash($this->findByPrimaryKey($actualEntity)), spl_object_hash($actualEntity));
    }

    /**
     * @param Product $product
     * @return null|Category
     */
    protected function getCategory(Product $product)
    {
        return $this->registry
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
    }

    /**
     * @return InsertFromSelectQueryExecutor
     */
    protected function getInsertFromSelectExecutor()
    {
        return $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }

    /**
     * @param BaseProductVisibilityResolved $visibilityResolved
     * @return BaseProductVisibilityResolved
     */
    abstract public function findByPrimaryKey($visibilityResolved);

    /**
     * @return array
     */
    abstract public function insertByCategoryDataProvider();

    /**
     * @return array
     */
    abstract public function insertStaticDataProvider();

    /**
     * @return array
     */
    abstract public function clearTableDataProvider();

    /**
     * @return AbstractVisibilityRepository
     */
    abstract protected function getRepository();

    /**
     * @return EntityRepository
     */
    abstract protected function getSourceRepository();

    /**
     * @return BaseProductVisibilityResolved[]
     */
    abstract protected function getResolvedValues();

    /**
     * @param BaseProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param object $targetEntity
     * @param Website $website
     *
     * @return BaseProductVisibilityResolved|null
     */
    abstract protected function getResolvedVisibility($visibilities, Product $product, $targetEntity, Website $website);

    /**
     * @param VisibilityInterface[]|null $sourceVisibilities
     * @param BaseProductVisibilityResolved $resolveVisibility
     * @return VisibilityInterface|null
     */
    abstract protected function getSourceVisibilityByResolved($sourceVisibilities, $resolveVisibility);
}
