<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocaleCodeFormatter;
use OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;

/**
 * @covers \OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy
 * @dbIsolation
 */
class LocalizedFallbackValueAwareStrategyTest extends WebTestCase
{
    use EntityTrait;

    /** @var LocalizedFallbackValueAwareStrategy */
    protected $strategy;

    protected function setUp()
    {
        $this->initClient();

        $container = $this->getContainer();

        if (!$container->hasParameter('orob2b_product.entity.product.class')) {
            $this->markTestSkipped('ProductBundle is missing');
        }

        $this->loadFixtures(
            ['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']
        );

        $container->get('oro_importexport.field.database_helper')->onClear();

        $this->strategy = new LocalizedFallbackValueAwareStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.import.helper'),
            $container->get('oro_importexport.field.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator')
        );
        $this->strategy->setLocalizedFallbackValueClass(
            $container->getParameter('orob2b_fallback.entity.localized_fallback_value.class')
        );
    }

    /**
     * @param array $entityData
     * @param array $expectedNames
     * @param array $itemData
     *
     * @dataProvider processDataProvider
     */
    public function testProcess(array $entityData = [], array $expectedNames = [], array $itemData = [])
    {
        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName($productClass);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find('in_stock');

        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $entity */
        $entity = $this->getEntity($productClass, $entityData);
        $entity->setInventoryStatus($inventoryStatus);

        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $result */
        $result = $this->strategy->process($entity);

        foreach ($result->getNames() as $localizedFallbackValue) {
            $localeCode = LocaleCodeFormatter::formatName($localizedFallbackValue->getLocale());
            $this->assertArrayHasKey($localeCode, $expectedNames);

            $expectedName = $expectedNames[$localeCode];
            if (!empty($expectedName['reference'])) {
                /**
                 * Validate that id matched from existing collection and does not affect other entities
                 * @var LocalizedFallbackValue $reference
                 */
                $reference = $this->getReference($expectedName['reference']);
                $this->assertEquals($reference->getId(), $localizedFallbackValue->getId());
            } else {
                $this->assertNull($localizedFallbackValue->getId());
            }

            $this->assertEquals($expectedName['text'], $localizedFallbackValue->getText());
            $this->assertEquals($expectedName['string'], $localizedFallbackValue->getString());
            $this->assertEquals($expectedName['fallback'], $localizedFallbackValue->getFallback());
        }
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        $localeEnUs = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', ['code' => 'en_US']);
        $localeEnCa = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', ['code' => 'en_CA']);

        return [
            [
                [
                    'sku' => 'product.1',
                    'primaryUnitPrecision' => $this->getEntity(
                        'OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                        [
                            'unit' => $this->getEntity(
                                'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'kg']
                            ),
                            'precision' => 3,
                        ]
                    ),
                    'names' => new ArrayCollection(
                        [
                            $this->getEntity(
                                'OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue',
                                ['string' => 'product.1 Default Title']
                            ),
                            $this->getEntity(
                                'OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue',
                                [
                                    'string' => 'product.1 en_US Title',
                                    'fallback' => 'parent_locale',
                                    'locale' => $localeEnUs,
                                ]
                            ),
                            $this->getEntity(
                                'OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue',
                                ['string' => 'product.1 en_CA Title', 'locale' => $localeEnCa]
                            ),
                        ]
                    ),
                ],
                [
                    'default' => [
                        'reference' => 'product.1.names.default',
                        'string' => 'product.1 Default Title',
                        'text' => null,
                        'fallback' => null,
                    ],
                    'en_US' => [
                        'reference' => 'product.1.names.en_US',
                        'string' => 'product.1 en_US Title',
                        'text' => null,
                        'fallback' => 'system',
                    ],
                    'en_CA' => [
                        'reference' => null,
                        'string' => 'product.1 en_CA Title',
                        'text' => null,
                        'fallback' => null,
                    ],
                ],
                [
                    'sku' => 'product.1',
                    'names' => [
                        'en_US' => [
                            'string' => 'product.1 en_US Title',
                            'fallback' => 'parent_locale',
                        ],
                        'en_CA' => [
                            'string' => 'product.1 en_CA Title',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $entityData
     * @param callable $resultCallback
     * @dataProvider skippedDataProvider
     */
    public function testProcessSkipped(array $entityData, callable $resultCallback)
    {
        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName($productClass);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find('in_stock');

        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $entity */
        $entity = $this->getEntity($productClass, $entityData);
        $entity->setInventoryStatus($inventoryStatus);
        $entity->setOwner(
            $this->getContainer()->get('doctrine')->getRepository('OroOrganizationBundle:BusinessUnit')->findOneBy([])
        );

        $resultCallback($this->strategy->process($entity));
    }

    /**
     * @return array
     */
    public function skippedDataProvider()
    {
        $localeEnUs = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', ['code' => 'en_US']);

        return [
            'new product, no fallback from another entity' => [
                [
                    'sku' => 'new_sku',
                    'primaryUnitPrecision' => $this->getEntity(
                        'OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                        [
                            'unit' => $this->getEntity(
                                'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'kg']
                            ),
                            'precision' => 3,
                        ]
                    ),
                ],
                function ($product) {
                    $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $product);

                    /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $product */
                    $this->assertNull($product->getId());
                    $this->assertEmpty($product->getNames()->toArray());
                },
            ],
            'existing product with, id not mapped for new fallback' => [
                [
                    'sku' => 'product.4',
                    'primaryUnitPrecision' => $this->getEntity(
                        'OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                        [
                            'unit' => $this->getEntity(
                                'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'each']
                            ),
                            'precision' => 0,
                        ]
                    ),
                    'names' => new ArrayCollection(
                        [
                            $this->getEntity(
                                'OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue',
                                ['string' => 'product.4 Default Title']
                            ),
                            $this->getEntity(
                                'OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue',
                                ['string' => 'product.4 en_US Title', 'locale' => $localeEnUs]
                            ),
                        ]
                    ),
                ],
                function ($product) {
                    $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $product);

                    /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $product */
                    $this->assertNotNull($product->getId());
                    $this->assertNotEmpty($product->getNames()->toArray());
                    $this->assertNull($product->getNames()->last()->getId());
                },
            ],
        ];
    }
}
