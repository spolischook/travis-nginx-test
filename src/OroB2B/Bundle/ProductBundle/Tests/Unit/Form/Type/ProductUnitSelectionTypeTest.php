<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitHolderType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitSelectionType;

class ProductUnitSelectionTypeTest extends FormIntegrationTestCase
{
    /** @var ProductUnitSelectionType */
    protected $formType;

    /**
     * @var array
     */
    protected $units = ['test01', 'test02'];

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, array $params) {
                    return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
                }
            );
        $productUnitLabelFormatter = new ProductUnitLabelFormatter($this->translator);
        $this->formType = new ProductUnitSelectionType($productUnitLabelFormatter, $this->translator);
        $this->formType->setEntityClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->prepareChoices());
        $productUnitSelectionType = new StubProductUnitSelectionType([1], ProductUnitSelectionType::NAME);

        return [
            new PreloadedExtension(
                [
                    'entity' => $entityType,
                    $productUnitSelectionType->getName() => $productUnitSelectionType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(static::isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    static::assertEquals(
                        [
                            'class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                            'property' => 'code',
                            'compact' => false,
                            'choices_updated' => false,
                            'required' => true,
                            'empty_label' => 'orob2b.product.productunit.removed',
                        ],
                        $options
                    );
                }
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param array $expectedLabels
     * @param string $submittedData
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, array $expectedLabels, $submittedData)
    {
        $form = $this->factory->create($this->formType, null, $inputOptions);

        $precision1 = new ProductUnitPrecision();
        $unit1 = new ProductUnit();
        $unit1->setCode('test01');
        $precision1->setUnit($unit1);
        $precision2 = new ProductUnitPrecision();
        $unit2 = new ProductUnit();
        $unit2->setCode('test02');
        $precision2->setUnit($unit2);

        $productUnitHolder = $this->createProductUnitHolder(
            1,
            'sku',
            $unit1,
            $this->createProductHolder(
                1,
                'sku',
                (new Product())->addUnitPrecision($precision1)->addUnitPrecision($precision2)
            )
        );

        $formParent = $this->factory->create(new StubProductUnitHolderType(), $productUnitHolder);
        $form->setParent($formParent);
        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $view = $form->createView();
        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());
        $choices = $view->vars['choices'];

        foreach ($choices as $choice) {
            $label = array_shift($expectedLabels);
            $this->assertEquals($label, $choice->label);
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'without compact option' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                ],
                'expectedLabels' => [
                    'orob2b.product_unit.test01.label.full',
                    'orob2b.product_unit.test02.label.full',
                ],
                'submittedData' => 'test01',
            ],
            'with compact option' => [
                'inputOptions' => [
                    'compact' => true,
                ],
                'expectedOptions' => [
                    'compact' => true,
                ],
                'expectedLabels' => [
                    'orob2b.product_unit.test01.label.short',
                    'orob2b.product_unit.test02.label.short',
                ],
                'submittedData' => 'test02',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    /**
     * @return array
     */
    protected function prepareChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @param boolean $withParent
     *
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData = [], array $expectedData = [], $withParent = true)
    {
        $form = $this->factory->create($this->formType, null, $inputData['options']);

        if ($withParent) {
            $formParent = $this->factory->create(new StubProductUnitHolderType(), $inputData['productUnitHolder']);
        } else {
            $formParent = null;
        }

        $form->setParent($formParent);

        $view = $form->createView();
        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());

        if (isset($view->vars['choices'])) {
            $choices = [];
            /* @var $choice ChoiceView */
            foreach ($view->vars['choices'] as $choice) {
                $choices[$choice->value] = $choice->label;
            }
            $view->vars['choices'] = $choices;
        }

        foreach ($expectedData as $field => $value) {
            if (!isset($view->vars[$field])) {
                $view->vars[$field] = null;
            }
            static::assertEquals($value, $view->vars[$field]);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider()
    {
        $precision = new ProductUnitPrecision();
        $unit = new ProductUnit();
        $unit->setCode('code');
        $precision->setUnit($unit);

        return [
            'empty item' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => null,
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine($this->units, $this->units),
                ],
            ],
            'without parent form' => [
                'inputData' => [
                    'options' => [],
                    'productHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine($this->units, $this->units),
                ],
                false,
            ],
            'filled item' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine($this->units, $this->units),
                ],
            ],
            'existing product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.full',
                    ],
                ],
            ],
            'existing product and compact mode' => [
                'inputData' => [
                    'options' => [
                        'compact' => true,
                    ],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.short',
                    ],
                ],
            ],
            'deleted product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        null,
                        $this->createProductHolder(1, 'sku', null)
                    ),
                ],
                'expectedData' => [
                    'choices' => array_combine($this->units, $this->units),
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param $productUnitCode
     * @param ProductUnit $productUnit
     * @param ProductHolderInterface $productHolder
     * @return ProductUnitHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductUnitHolder(
        $id,
        $productUnitCode,
        ProductUnit $productUnit = null,
        ProductHolderInterface $productHolder = null
    ) {
        /* @var $productUmitHolder \PHPUnit_Framework_MockObject_MockObject|ProductUnitHolderInterface */
        $productUnitHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface');
        $productUnitHolder
            ->expects(static::any())
            ->method('getEntityIdentifier')
            ->willReturn($id);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductHolder')
            ->willReturn($productHolder);

        return $productUnitHolder;
    }

    /**
     * @param int $id
     * @param string $productSku
     * @param Product $product
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function createProductHolder($id, $productSku, Product $product = null)
    {
        /* @var $productHolder \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface */
        $productHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolder
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        $productHolder
            ->expects(static::any())
            ->method('getProduct')
            ->willReturn($product);

        $productHolder
            ->expects(static::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $productHolder;
    }

    public function testPostSetData()
    {
        $productUnit = new ProductUnit();
        $code = 'sku';
        $productUnit->setCode($code);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($productUnit);
        $productHolder = $this->createProductHolder(1, $code, (new Product())->addUnitPrecision($unitPrecision));
        $productUnitHolder = $this->createProductUnitHolder(1, $code, $productUnit, $productHolder);

        $form = $this->factory->create($this->formType);
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->once())->method('getData')->willReturn($productUnitHolder);
        $form->setParent($parentForm);

        $parentForm->expects($this->once())->method('add')->with(
            $this->isType('string'),
            $this->isType('string'),
            $this->logicalAnd(
                $this->isType('array'),
                $this->callback(
                    function (array $options) use ($productUnit) {
                        $this->assertArrayHasKey('choices_updated', $options);
                        $this->assertTrue($options['choices_updated']);

                        $this->assertArrayHasKey('choices', $options);
                        $this->assertEquals([$productUnit], $options['choices']);

                        return true;
                    }
                )
            )
        );

        $form->setData($productUnitHolder);
    }
}
