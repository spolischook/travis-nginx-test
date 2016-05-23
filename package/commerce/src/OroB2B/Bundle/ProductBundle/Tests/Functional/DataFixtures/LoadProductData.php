<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LoadProductData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const PRODUCT_1 = 'product.1';
    const PRODUCT_2 = 'product.2';
    const PRODUCT_3 = 'product.3';
    const PRODUCT_4 = 'product.4';
    const PRODUCT_5 = 'product.5';
    const PRODUCT_6 = 'product.6';
    const PRODUCT_7 = 'product.7';
    const PRODUCT_8 = 'product.8';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $enumInventoryStatuses */
        $enumInventoryStatuses = $manager->getRepository($inventoryStatusClassName)->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'product_fixture.yml';

        $data = Yaml::parse(file_get_contents($filePath));

        foreach ($data as $item) {

            $unit = $this->getReference('product_unit.milliliter');

            $unitPrecision = new ProductUnitPrecision();
            $unitPrecision->setUnit($unit)
                ->setPrecision((int)$item['primaryProductUnit']['precision'])
                ->setConversionRate(1)
                ->setSell(true);

            $product = new Product();
            $product
                ->setSku($item['productCode'])
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setInventoryStatus($inventoryStatuses[$item['inventoryStatus']])
                ->setStatus($item['status'])
                ->setPrimaryUnitPrecision($unitPrecision);

            if (!empty($item['names'])) {
                foreach ($item['names'] as $name) {
                    $product->addName($this->createValue($name));
                }
            }

            if (!empty($item['descriptions'])) {
                foreach ($item['descriptions'] as $name) {
                    $product->addDescription($this->createValue($name));
                }
            }

            if (!empty($item['shortDescriptions'])) {
                foreach ($item['shortDescriptions'] as $name) {
                    $product->addShortDescription($this->createValue($name));
                }
            }

            $manager->persist($product);
            $this->addReference($product->getSku(), $product);
        }

        $manager->flush();
    }

    /**
     * @param array $name
     * @return LocalizedFallbackValue
     */
    protected function createValue(array $name)
    {
        $value = new LocalizedFallbackValue();
        if (array_key_exists('locale', $name)) {
            /** @var Locale $locale */
            $locale = $this->getReference($name['locale']);
            $value->setLocale($locale);
        }
        if (array_key_exists('fallback', $name)) {
            $value->setFallback($name['fallback']);
        }
        if (array_key_exists('string', $name)) {
            $value->setString($name['string']);
        }
        if (array_key_exists('text', $name)) {
            $value->setText($name['text']);
        }
        $this->setReference($name['reference'], $value);

        return $value;
    }

    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}
