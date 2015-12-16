<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;

class PriceListProductPriceTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    /**
     * @var PriceListProductPriceType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $units = [
        'item',
        'kg'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new PriceListProductPriceType();
        $this->formType->setDataClass('OroB2B\Bundle\PricingBundle\Entity\ProductPrice');

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                1 => $this->getProductEntityWithPrecision(1, 'kg', 3),
                2 => $this->getProductEntityWithPrecision(2, 'kg', 3)
            ]
        );

        $productUnitSelection = new EntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $productSelect = new ProductSelectTypeStub();

        $priceType = new PriceType();
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    ProductSelectType::NAME => $productSelect,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    PriceType::NAME => $priceType,
                    CurrencySelectionType::NAME => new CurrencySelectionType($configManager, $localeSettings),
                    QuantityTypeTrait::$name => $this->getQuantityType()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param ProductPrice $defaultData
     * @param array $submittedData
     * @param ProductPrice $expectedData
     * @param boolean $rounding
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        array $submittedData,
        ProductPrice $expectedData,
        $rounding = false
    ) {
        if ($rounding) {
            $this->addRoundingServiceExpect();
        }

        $form = $this->factory->create($this->formType, $defaultData, []);

        // unit placeholder must not be available for specific entity
        $unitPlaceholder = $form->get('unit')->getConfig()->getOption('placeholder');
        $defaultData->getId() ? $this->assertNull($unitPlaceholder) : $this->assertNotNull($unitPlaceholder);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['GBP']);

        /** @var Product $expectedProduct */
        $expectedProduct = $this->getProductEntityWithPrecision(2, 'kg', 3);
        $expectedPrice1 = (new Price())->setValue(42)->setCurrency('USD');
        $expectedPrice2 = (new Price())->setValue(42)->setCurrency('GBP');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setProduct($expectedProduct)
            ->setQuantity(123)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setPrice($expectedPrice1)
            ->setPriceList($priceList);

        $expectedProductPrice2 = clone $expectedProductPrice;
        $expectedProductPrice2
            ->setQuantity(123.556)
            ->setPrice($expectedPrice2);

        $defaultProductPrice = new ProductPrice();
        $defaultProductPrice->setPriceList($priceList);

        $defaultProductPriceWithId = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\ProductPrice', 1);
        $defaultProductPriceWithId->setPriceList($priceList);
        $defaultProductPriceWithId->setPrice((new Price())->setCurrency('USD')->setValue(1));

        return [
            'product price without data' => [
                'defaultData'   => $defaultProductPriceWithId,
                'submittedData' => [
                    'product'  => null,
                    'quantity'  => null,
                    'unit'  => null,
                    'price'  => [
                        'value'    => $defaultProductPriceWithId->getPrice()->getValue(),
                        'currency' => $defaultProductPriceWithId->getPrice()->getCurrency()
                    ],
                ],
                'expectedData'  => clone $defaultProductPriceWithId,
                'rounding'      => false
            ],
            'product price with data' => [
                'defaultData'   => clone $defaultProductPrice,
                'submittedData' => [
                    'product' => 2,
                    'quantity'  => 123,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData' => $expectedProductPrice,
                'rounding'      => true
            ],
            'product price with data for rounding' => [
                'defaultData'   => clone $defaultProductPrice,
                'submittedData' => [
                    'product' => 2,
                    'quantity'  => 123.5555,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'GBP'
                    ]
                ],
                'expectedData' => $expectedProductPrice2,
                'rounding'     => true
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(PriceListProductPriceType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function prepareProductUnitSelectionChoices()
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
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }

    /**
     * @param integer $productId
     * @param string $unitCode
     * @param integer $precision
     * @return Product
     */
    protected function getProductEntityWithPrecision($productId, $unitCode, $precision = 0)
    {
        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }
}
