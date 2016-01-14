<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes as TaxFixture;

/**
 * @dbIsolation
 */
class ProductTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes']);
    }

    public function testFindOneByProduct()
    {
        /** @var Product $product5 */
        $product5 = $this->getReference(LoadProductData::PRODUCT_5);
        $this->assertNull($this->getRepository()->findOneByProduct($product5));

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $expectedTaxCode = $this->getRepository()->findOneByProduct($product1);

        /** @var ProductTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode1->getId());
    }

    public function testFindNewProduct()
    {
        $this->assertEmpty($this->getRepository()->findOneByProduct(new Product()));
    }

    /**
     * @return ProductTaxCodeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_tax.entity.product_tax_code.class')
        );
    }
}
