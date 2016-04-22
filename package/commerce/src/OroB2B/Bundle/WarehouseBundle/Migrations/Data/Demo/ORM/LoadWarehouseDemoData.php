<?php

namespace OroB2B\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class LoadWarehouseDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use UserUtilityTrait;

    const MAIN_WAREHOUSE = 'warehouse.main';
    const ADDITIONAL_WAREHOUSE = 'warehouse.additional';

    /**
     * @var array
     */
    protected $warehouses = [
        self::MAIN_WAREHOUSE => [
            'name' => 'Main Warehouse',
            'generateLevels' => true,
        ],
        self::ADDITIONAL_WAREHOUSE => [
            'name' => 'Additional Warehouse',
        ],
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        foreach ($this->warehouses as $reference => $row) {
            $warehouse = new Warehouse();
            $warehouse
                ->setName($row['name'])
                ->setOwner($businessUnit)
                ->setOrganization($organization);
            $manager->persist($warehouse);

            $precisions = $this->getPrecisions();
            if (!empty($row['generateLevels'])) {
                foreach ($precisions as $precision) {
                    $level = new WarehouseInventoryLevel();
                    $level
                        ->setWarehouse($warehouse)
                        ->setProductUnitPrecision($precision)
                        ->setQuantity(mt_rand(1, 100));
                    $manager->persist($level);
                }
            }

            $this->addReference($reference, $warehouse);
        }

        $manager->flush();
    }

    /**
     * @return ProductUnitPrecision[]
     */
    protected function getPrecisions()
    {
        return $this->container->get('doctrine')->getRepository('OroB2BProductBundle:ProductUnitPrecision')->findAll();
    }
}
