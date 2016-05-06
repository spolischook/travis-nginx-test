<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptionsValidator;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Type\DimensionsType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use OroB2B\Bundle\ShippingBundle\Form\Type\WeightType;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

class ProductShippingOptionsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ProductShippingOptionsType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductShippingOptionsType($this->getProvider());
        $this->formType->setDataClass('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions');
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $constraint = new UniqueProductUnitShippingOptions();

        $UniqueEntity = $this->getMockBuilder('Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator')
            ->disableOriginalConstructor()->getMock();

        return [
            $constraint->validatedBy() => new UniqueProductUnitShippingOptionsValidator(),
            'doctrine.orm.validator.unique' => $UniqueEntity
        ];
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'product' => null,
                    'data_class' => 'OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions',
                    'intention' => 'product_shipping_options',
                    'by_reference' => false,
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_shipping_product_shipping_options', $this->formType->getName());
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $options
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, array $submittedData, $expectedData, $defaultData = null, array $options = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty form' => [
                'isValid' => false,
                'submittedData' => [],
                'expectedData' => $this->getProductShippingOptions(),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty product' => [
                'isValid' => false,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'length' => 2,
                        'width' => 3,
                        'height' => 4,
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], [2, 3, 4, 'mm'], 'pl')
                        ->setProduct(null),
                'defaultData' => $this->getProductShippingOptions()
                        ->setProduct(null),
            ],
            'empty unit' => [
                'isValid' => false,
                'submittedData' => [
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'length' => 2,
                        'width' => 3,
                        'height' => 4,
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions(null, [1, 'kg'], [2, 3, 4, 'mm'], 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty weight' => [
                'isValid' => false,
                'submittedData' => [
                    'productUnit' => 'item',
                    'dimensions' => [
                        'length' => 2,
                        'width' => 3,
                        'height' => 4,
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', null, [2, 3, 4, 'mm'], 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty dimensions' => [
                'isValid' => false,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], null, 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty freightClass' => [
                'isValid' => false,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'length' => 2,
                        'width' => 3,
                        'height' => 4,
                        'unit' => 'mm',
                    ],
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], [2, 3, 4, 'mm'], null),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'valid form' => [
                'isValid' => true,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'length' => 2,
                        'width' => 3,
                        'height' => 4,
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], [2, 3, 4, 'mm'], 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
        ];
    }

    /**
     *
     * @param string $unitCode
     * @param array $weight
     * @param array $dimensions
     * @param string $freightClass
     *
     * @return ProductShippingOptions
     */
    protected function getProductShippingOptions(
        $unitCode = null,
        array $weight = null,
        array $dimensions = null,
        $freightClass = null
    ) {
        $productShippingOptions = new ProductShippingOptions();
        $productShippingOptions->setProduct(new Product());

        if ($unitCode) {
            $productShippingOptions->setProductUnit(
                $this->getEntity(
                    'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                    [
                        'code' => $unitCode,
                        'defaultPrecision' => 1,
                    ]
                )
            );
        }

        if ($weight) {
            $productShippingOptions->setWeight(
                $this->getEntity(
                    'OroB2B\Bundle\ShippingBundle\Model\Weight',
                    [
                        'value' => $weight[0],
                        'unit' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                                [
                                    'code' => $weight[1]
                                ]
                            ),
                    ]
                )
            );
        }

        if ($dimensions) {
            $productShippingOptions->setDimensions(
                $this->getEntity(
                    'OroB2B\Bundle\ShippingBundle\Model\Dimensions',
                    [
                        'length' => $dimensions[0],
                        'width' => $dimensions[1],
                        'height' => $dimensions[2],
                        'unit' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit',
                                [
                                    'code' => $dimensions[3]
                                ]
                            ),
                    ]
                )
            );
        }

        if ($freightClass) {
            $productShippingOptions->setFreightClass(
                $this->getEntity(
                    'OroB2B\Bundle\ShippingBundle\Entity\FreightClass',
                    [
                        'code' => $freightClass,
                    ]
                )
            );
        }

        return $productShippingOptions;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productUnitSelectionType = new EntityType(
            [
                'each' => $this->getEntity(
                    'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                    [
                        'code' => 'each',
                        'defaultPrecision' => 1,
                    ]
                ),
                'item' => $this->getEntity(
                    'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                    [
                        'code' => 'item',
                        'defaultPrecision' => 1
                    ]
                ),
            ],
            ProductUnitSelectionType::NAME
        );

        $weightType = new WeightType($this->getProvider());
        $weightType->setDataClass('OroB2B\Bundle\ShippingBundle\Model\Weight');

        $dimensionsType = new DimensionsType($this->getProvider());
        $dimensionsType->setDataClass('OroB2B\Bundle\ShippingBundle\Model\Dimensions');

        return [
            new PreloadedExtension(
                [
                    $weightType->getName() => $weightType,
                    $dimensionsType->getName() => $dimensionsType,
                    'entity' => new EntityType([
                            'mm' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit',
                                ['code' => 'mm']
                            ),
                            'cm' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit',
                                ['code' => 'cm']
                            ),
                            'mg' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                                ['code' => 'mg']
                            ),
                            'kg' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                                ['code' => 'kg']
                            ),
                            'pl' => $this->getEntity(
                                'OroB2B\Bundle\ShippingBundle\Entity\FreightClass',
                                ['code' => 'pl']
                            )
                        ]),
                    $productUnitSelectionType->getName() => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    protected function getProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractMeasureUnitProvider $provider */
        $provider = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(null);
        $provider->expects($this->any())
            ->method('getUnits')
            ->willReturn([]);

        return $provider;
    }
}
