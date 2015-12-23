<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $where = ['account' => $account, 'product' => $product, 'website' => $website];
        $this->assertFalse($repository->hasEntity($where));
        $this->assertInsert(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
        $this->assertUpdate(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertDelete($repository, $where);
    }

    public function testDeleteByProduct()
    {
        $repository = $this->getRepository();
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertNotEmpty($visibilities);
        $repository->deleteByProduct($product);
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertEmpty($visibilities, 'Deleting has failed');
    }
    public function testInsertByProduct()
    {
        $repository = $this->getRepository();
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $repository->deleteByProduct($product);
        $repository->insertByProduct($product, $this->getInsertFromSelectExecutor(), false, null);
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertSame(1, count($visibilities));
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategoryDataProvider()
    {
        return [
            'withoutWebsite' => [
                'websiteReference' => null,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_8,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                ],
            ],
            'withWebsite1' => [
                'websiteReference' => LoadWebsiteData::WEBSITE1,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_8,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                ],
            ],
            'withWebsite2' => [
                'websiteReference' => LoadWebsiteData::WEBSITE2,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [],
            ],
        ];
    }
    public function clearTableDataProvider()
    {
        return ['expected_rows' => [5]];
    }
    /**
     * @inheritDoc
     */
    public function insertStaticDataProvider()
    {
        return ['expected_rows' => [4]];
    }
    /**
     * @return AccountProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findAll();
    }
    /**
     * @param AccountProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Account $account
     * @param Website $website
     *
     * @return AccountProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(
        $visibilities,
        Product $product,
        $account,
        Website $website
    ) {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() == $product->getId()
                && $visibility->getAccount()->getId() == $account->getId()
                && $visibility->getWebsite()->getId() == $website->getId()
            ) {
                return $visibility;
            }
        }
        return null;
    }
    /**
     * @param null|AccountProductVisibility[] $sourceVisibilities
     * @param AccountProductVisibilityResolved $resolveVisibility
     * @return null|AccountProductVisibility
     */
    protected function getSourceVisibilityByResolved($sourceVisibilities, $resolveVisibility)
    {
        foreach ($sourceVisibilities as $visibility) {
            if ($resolveVisibility->getProduct()->getId() == $visibility->getProduct()->getId()
                && $resolveVisibility->getAccount()->getId() == $visibility->getAccount()->getId()
                && $resolveVisibility->getWebsite()->getId() == $visibility->getWebsite()->getId()
            ) {
                return $visibility;
            }
        }
        return null;
    }
    /**
     * @return EntityRepository
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );
    }
    /**
     * @return AccountProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }
    /**
     * @param AccountProductVisibilityResolved $visibilityResolved
     * @return AccountProductVisibilityResolved|null
     */
    public function findByPrimaryKey($visibilityResolved)
    {
        return $this->getRepository()->findByPrimaryKey(
            $visibilityResolved->getAccount(),
            $visibilityResolved->getProduct(),
            $visibilityResolved->getWebsite()
        );
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => 'Default']);
    }
}
