<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowCollectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;

class ProductRowCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductRowCollectionType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new ProductRowCollectionType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_collection' => new CollectionType(),
                    ProductRowType::NAME => new ProductRowType(),
                    ProductAutocompleteType::NAME => new StubProductAutocompleteType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @param array|null $defaultData
     * @param array|null $submittedData
     * @param array|null $expectedData
     * @param array $options
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, array $options)
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
        return [
            'without submitted data' => [
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => [],
                'options' => []
            ],
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    [
                        'productSku' => 'SKU_001',
                        'productQuantity' => ''
                    ],
                    [
                        'productSku' => 'SKU_002',
                        'productQuantity' => '20'
                    ]
                ],
                'expectedData' => [
                    [
                        'productSku' => 'SKU_001',
                        'productQuantity' => '1'
                    ],
                    [
                        'productSku' => 'SKU_002',
                        'productQuantity' => '20'
                    ]
                ],
                'options' => []
            ],
            'with default data' => [
                'defaultData' => [
                    [
                        'productSku' => 'SKU',
                        'productQuantity' => '42'
                    ]
                ],
                'submittedData' => [
                    [
                        'productSku' => 'SKU_003',
                        'productQuantity' => '30'
                    ],
                    [
                        'productSku' => 'SKU_004',
                        'productQuantity' => '40'
                    ],
                    [
                        'productSku' => 'SKU_005',
                        'productQuantity' => '50'
                    ],
                    [
                        'productSku' => '',
                        'productQuantity' => ''
                    ],
                    [
                        'productSku' => '',
                        'productQuantity' => ''
                    ],
                ],
                'expectedData' => [
                    [
                        'productSku' => 'SKU_003',
                        'productQuantity' => '30'
                    ],
                    [
                        'productSku' => 'SKU_004',
                        'productQuantity' => '40'
                    ],
                    [
                        'productSku' => 'SKU_005',
                        'productQuantity' => '50'
                    ]
                ],
                'options' => []
            ]
        ];
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('products', $options);
                        $this->assertNull($options['products']);
                        return true;
                    }
                )
            );

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductRowCollectionType::NAME, $this->formType->getName());
    }
}
