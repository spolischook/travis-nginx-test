<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductVariantLinksType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductStatusType;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductCustomFieldsChoiceTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\EnumSelectTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;
use OroB2B\Bundle\ProductBundle\Provider\ProductStatusProvider;

class ProductTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /** @var  DefaultProductUnitProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $defaultProductUnitProvider;

    /**
     * @var array
     */
    protected $exampleCustomFields = [
        'size'  => 'Size Label',
        'color' => 'Color Label'
    ];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->getMock('OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface');
        $this->defaultProductUnitProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');
        $productUnit->setDefaultPrecision('3');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit)->setPrecision('3');

        $this->defaultProductUnitProvider
            ->expects($this->any())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($productUnitPrecision));

        $this->type = new ProductType($this->defaultProductUnitProvider, $this->roundingService);
        $this->type->setDataClass(self::DATA_CLASS);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type, $this->roundingService);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productUnitPrecision = new ProductUnitPrecisionType();
        $productUnitPrecision->setDataClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision');

        $stubEnumSelectType = new EnumSelectTypeStub();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider $configProvider */
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|Translator $translator */
        $translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    $stubEnumSelectType->getName() => $stubEnumSelectType,
                    ImageType::NAME => new ImageTypeStub(),
                    OroCollectionType::NAME => new OroCollectionType(),
                    ProductUnitPrecisionType::NAME => $productUnitPrecision,
                    ProductUnitPrecisionCollectionType::NAME => new ProductUnitPrecisionCollectionType(),
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionTypeStub(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ],
                        ProductUnitSelectionType::NAME
                    ),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    ProductCustomFieldsChoiceType::NAME => new ProductCustomFieldsChoiceTypeStub(
                        $this->exampleCustomFields
                    ),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType([]),
                    ProductVariantLinksType::NAME => new ProductVariantLinksType(),
                    ProductStatusType::NAME => new ProductStatusType(new ProductStatusProvider()),
                ],
                [
                    'form' => [
                        new TooltipFormExtension($configProvider, $translator),
                        new IntegerExtension()
                    ]
                ]
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param Product $defaultData
     * @param array $submittedData
     * @param Product $expectedData
     * @param boolean $rounding
     */
    public function testSubmit(Product $defaultData, $submittedData, Product $expectedData, $rounding = false)
    {
        if ($rounding) {
            $this->roundingService->expects($this->once())
                ->method('round')
                ->willReturnCallback(
                    function ($value, $precision) {
                        return round($value, $precision);
                    }
                );
        }

        $form = $this->factory->create($this->type, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var Product $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'simple product' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'variantFields' => array_keys($this->exampleCustomFields)
                ],
                'expectedData'  => $this->createExpectedProductEntity(),
                'rounding' => false
            ],
            'product with unitPrecisions' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [
                        [
                            'unit' => 'kg',
                            'precision' => 3
                        ]
                    ],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'variantFields' => array_keys($this->exampleCustomFields)
                ],
                'expectedData'  => $this->createExpectedProductEntity(true),
                'rounding' => false
            ],
            'product with names and descriptions' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'names' => [
                        ['string' => 'first name'],
                        ['string' => 'second name'],
                    ],
                    'descriptions' => [
                        ['text' => 'first description'],
                        ['text' => 'second description'],
                    ],
                    'shortDescriptions' => [
                        ['text' => 'first short description'],
                        ['text' => 'second short description'],
                    ],
                    'variantFields' => array_keys($this->exampleCustomFields)
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, true),
                'rounding' => false
            ],
            'simple product without hasVariants' => [
                'defaultData'   => $this->createDefaultProductEntity(false),
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, false, false),
                'rounding' => false
            ],
        ];
    }

    /**
     * @param bool|false $withProductUnitPrecision
     * @param bool|false $withNamesAndDescriptions
     * @param bool|true $hasVariants
     * @return StubProduct
     */
    protected function createExpectedProductEntity(
        $withProductUnitPrecision = false,
        $withNamesAndDescriptions = false,
        $hasVariants = true
    ) {
        $expectedProduct = new StubProduct();

        $expectedProduct->setHasVariants($hasVariants);

        if ($hasVariants) {
            $expectedProduct->setVariantFields(array_keys($this->exampleCustomFields));
        }

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        if ($withProductUnitPrecision) {
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($expectedProduct)
                ->setUnit($productUnit)
                ->setPrecision(3);

            $expectedProduct->addUnitPrecision($productUnitPrecision);
        }

        if ($withNamesAndDescriptions) {
            $expectedProduct
                ->addName($this->createLocalizedValue('first name'))
                ->addName($this->createLocalizedValue('second name'))
                ->addDescription($this->createLocalizedValue(null, 'first description'))
                ->addDescription($this->createLocalizedValue(null, 'second description'))
                ->addShortDescription($this->createLocalizedValue(null, 'first short description'))
                ->addShortDescription($this->createLocalizedValue(null, 'second short description'));
        }

        return $expectedProduct->setSku('test sku');
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, $this->createDefaultProductEntity());

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('unitPrecisions'));
        $this->assertFalse($form->has('hasVariants'));
    }

    public function testGetName()
    {
        $name = $this->type->getName();
        $this->assertEquals('orob2b_product', $this->type->getName());
    }

    /**
     * @param string|null $string
     * @param string|null $text
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text);

        return $value;
    }

    /**
     * @param bool|true $hasVariants
     * @return StubProduct
     */
    protected function createDefaultProductEntity($hasVariants = true)
    {
        $defaultProduct = new StubProduct();
        $defaultProduct->setHasVariants($hasVariants);

        if ($hasVariants) {
            $defaultProduct->setVariantFields(array_keys($this->exampleCustomFields));
        }

        return $defaultProduct;
    }
}
