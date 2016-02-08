<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormTypeInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserMultiSelectType;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;
use OroB2B\Bundle\SaleBundle\Validator\Constraints;

abstract class AbstractTest extends FormIntegrationTestCase
{
    const QP_TYPE1          = QuoteProduct::TYPE_REQUESTED;
    const QPO_PRICE_TYPE1   = QuoteProductOffer::PRICE_TYPE_UNIT;

    /**
     * @var FormTypeInterface
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteProductFormatter
     */
    protected $quoteProductFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteProductOfferFormatter
     */
    protected $quoteProductOfferFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->quoteProductFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->quoteProductFormatter->expects($this->any())
            ->method('formatTypeLabels')
            ->will($this->returnCallback(function (array $types) {
                return $types;
            }))
        ;

        $this->quoteProductOfferFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->quoteProductOfferFormatter->expects($this->any())
            ->method('formatPriceTypeLabels')
            ->will($this->returnCallback(function (array $types) {
                return $types;
            }))
        ;

        parent::setUp();
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    abstract public function submitProvider();

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $quoteProductConstraint = new Constraints\QuoteProduct();

        return [
            $quoteProductConstraint->validatedBy() => new Constraints\QuoteProductValidator(),
        ];
    }

    /**
     * @return QuoteProductOfferType
     */
    protected function prepareQuoteProductOfferType()
    {
        $quoteProductOfferType = new QuoteProductOfferType($this->quoteProductOfferFormatter);
        $quoteProductOfferType->setDataClass('OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer');

        return $quoteProductOfferType;
    }

    /**
     * @return QuoteProductRequestType
     */
    protected function prepareQuoteProductRequestType()
    {
        $quoteProductRequestType = new QuoteProductRequestType();
        $quoteProductRequestType->setDataClass('OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest');

        return $quoteProductRequestType;
    }

    /**
     * @return PriceType
     */
    protected function preparePriceType()
    {
        return PriceTypeGenerator::createPriceType();
    }

    /**
     * @return EntityType
     */
    protected function prepareProductEntityType()
    {
        $entityType = new EntityType(
            [
                2 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2),
                3 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 3),
            ]
        );

        return $entityType;
    }

    /**
     * @return EntityType
     */
    protected function prepareUserMultiSelectType()
    {
        return new EntityType(
            [
                1 => $this->getUser(1),
                2 => $this->getUser(2),
            ],
            UserMultiSelectType::NAME,
            [
                'multiple' => true
            ]
        );
    }

    /**
     * @return EntityType
     */
    protected function prepareAccountUserMultiSelectType()
    {
        return new EntityType(
            [
                10 => $this->getAccountUser(10),
                11 => $this->getAccountUser(11),
            ],
            AccountUserMultiSelectType::NAME,
            [
                'multiple' => true
            ]
        );
    }

    /**
     * @param array $codes
     * @return ProductUnit[]
     */
    protected function getProductUnits(array $codes)
    {
        $res = [];

        foreach ($codes as $code) {
            $res[] = (new ProductUnit())->setCode($code);
        }

        return $res;
    }

    /**
     * @return ProductUnitPrecision[]
     */
    protected function getProductUnitPrecisions()
    {
        return [
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('kg')),
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item')),
        ];
    }

    /**
     * @return EntityType
     */
    protected function prepareProductUnitSelectionType()
    {
        return new ProductUnitSelectionTypeStub(
            [
                'kg' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'),
                'item' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'),
            ]
        );
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $primaryKey
     * @return object
     */
    protected function getEntity($className, $id, $primaryKey = 'id')
    {
        static $entities = [];

        if (!isset($entities[$className])) {
            $entities[$className] = [];
        }

        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className;
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($primaryKey);
            $method->setAccessible(true);
            $method->setValue($entities[$className][$id], $id);
        }

        return $entities[$className][$id];
    }

    /**
     * @param string $className
     * @param array $fields
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockEntity($className, array $fields = [])
    {
        $mock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        foreach ($fields as $method => $value) {
            $mock->expects($this->any())
                ->method($method)
                ->will($this->returnValue($value))
            ;
        }

        return $mock;
    }

    /**
     * @param int $id
     * @return User
     */
    protected function getUser($id)
    {
        return $this->getEntity('Oro\Bundle\UserBundle\Entity\User', $id);
    }

    /**
     * @param int $id
     * @return AccountUser
     */
    protected function getAccountUser($id)
    {
        return $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $id);
    }

    /**
     * @param int $productId
     * @param int $type
     * @param string $comment
     * @param string $commentAccount
     * @param QuoteProductRequest[] $requests
     * @param QuoteProductOffer[] $offers
     * @return QuoteProduct
     */
    protected function getQuoteProduct(
        $productId = null,
        $type = null,
        $comment = null,
        $commentAccount = null,
        array $requests = [],
        array $offers = []
    ) {
        $product = null;

        if ($productId) {
            $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

            foreach ($this->getProductUnitPrecisions() as $precision) {
                $product->addUnitPrecision($precision);
            }
        }

        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setQuote($this->getEntity('OroB2B\Bundle\SaleBundle\Entity\Quote', $productId))
            ->setProduct($product)
            ->setType($type)
            ->setComment($comment)
            ->setCommentAccount($commentAccount)
        ;

        foreach ($requests as $request) {
            $quoteProduct->addQuoteProductRequest($request);
        }

        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        return $quoteProduct;
    }

    /**
     * @param int $productId
     * @param float $quantity
     * @param string $unitCode
     * @param int $priceType
     * @param Price $price
     * @return QuoteProductOffer
     */
    protected function getQuoteProductOffer(
        $productId = null,
        $quantity = null,
        $unitCode = null,
        $priceType = null,
        Price $price = null
    ) {
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuoteProduct($this->getQuoteProduct($productId));

        if (null !== $quantity) {
            $quoteProductOffer->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $quoteProductOffer->setProductUnit(
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $unitCode, 'code')
            );
        }

        $quoteProductOffer->setPriceType($priceType);

        if (null !== $price) {
            $quoteProductOffer->setPrice($price);
        }

        return $quoteProductOffer;
    }

    /**
     * @param int $productId
     * @param float $quantity
     * @param string $unitCode
     * @param Price $price
     * @return QuoteProductOffer
     */
    protected function getQuoteProductRequest(
        $productId = null,
        $quantity = null,
        $unitCode = null,
        Price $price = null
    ) {
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProductRequest->setQuoteProduct($this->getQuoteProduct($productId));

        if (null !== $quantity) {
            $quoteProductRequest->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $quoteProductRequest->setProductUnit(
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $unitCode, 'code')
            );
        }

        if (null !== $price) {
            $quoteProductRequest->setPrice($price);
        }

        return $quoteProductRequest;
    }
}
