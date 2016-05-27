<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LoadProductUnitPrecisions extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createProductUnitPrecision($manager, 'product.1', 'product_unit.liter');
        $this->createProductUnitPrecision($manager, 'product.1', 'product_unit.bottle', 2, 3, true);
        $this->createProductUnitPrecision($manager, 'product.2', 'product_unit.liter', 3);
        $this->createProductUnitPrecision($manager, 'product.2', 'product_unit.bottle', 1, 2, true);
        $this->createProductUnitPrecision($manager, 'product.2', 'product_unit.box');
        $this->createProductUnitPrecision($manager, 'product.3', 'product_unit.liter', 3);
        $this->createProductUnitPrecision($manager, 'product.4', 'product_unit.box');
        $this->createProductUnitPrecision($manager, 'product.5', 'product_unit.box');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $productReference
     * @param string $unitReference
     * @param int $precision
     * @param float $conversionRate
     * @param boolean $sell
     * @return ProductUnitPrecision
     */
    protected function createProductUnitPrecision(
        ObjectManager $manager,
        $productReference,
        $unitReference,
        $precision = 0,
        $conversionRate = 1.0,
        $sell = false
    ) {
        /** @var Product $productReference */
        $product = $this->getReference($productReference);
        /** @var ProductUnit $unitReference */
        $unit = $this->getReference($unitReference);

        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setProduct($product);
        $productUnitPrecision->setPrecision($precision);
        $productUnitPrecision->setUnit($unit);
        $productUnitPrecision->setConversionRate($conversionRate);
        $productUnitPrecision->setSell($sell);
        $manager->persist($productUnitPrecision);
        $this->addReference(
            sprintf('product_unit_precision.%s', implode('.', [$product->getSku(), $unit->getCode()])),
            $productUnitPrecision
        );

        return $productUnitPrecision;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits'
        ];
    }
}
