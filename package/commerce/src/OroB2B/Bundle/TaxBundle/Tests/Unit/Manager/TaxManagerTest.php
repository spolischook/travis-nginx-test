<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Manager;

use OroB2B\Bundle\TaxBundle\Event\TaxEventDispatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Manager\TaxValueManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

class TaxManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxManager */
    protected $manager;

    /**  @var \PHPUnit_Framework_MockObject_MockObject|TaxFactory */
    protected $factory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TaxEventDispatcher */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TaxValueManager */
    protected $taxValueManager;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Factory\TaxFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Event\TaxEventDispatcher')
            ->disableOriginalConstructor()->getMock();

        $this->taxValueManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxValueManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new TaxManager($this->factory, $this->eventDispatcher, $this->taxValueManager);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage TaxTransformerInterface is missing for stdClass
     */
    public function testTransformerNotFound()
    {
        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->taxValueManager->expects($this->never())->method($this->anything());

        $this->manager->loadTax(new \stdClass());
    }

    public function testNewEntity()
    {
        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn(new TaxValue());

        $this->manager->loadTax(new \stdClass());
    }

    public function testTaxValue()
    {
        $taxValue = new TaxValue();
        $taxResult = new Result([Result::UNIT => new ResultElement([ResultElement::INCLUDING_TAX => 10])]);
        $taxValue->setResult($taxResult);

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn($taxValue);

        $result = $this->manager->loadTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertSame($taxResult, $result);
    }

    public function testGetTaxNewResult()
    {
        $taxable = new Taxable();
        $this->factory->expects($this->exactly(2))->method('create')->willReturn($taxable);

        $this->taxValueManager->expects($this->never())->method($this->anything());

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->callback(
                    function ($dispatchedTaxable) use ($taxable) {
                        /** @var Taxable $dispatchedTaxable */
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Taxable', $dispatchedTaxable);
                        $this->assertSame($taxable, $dispatchedTaxable);

                        /** @var Result $dispatchedResult */
                        $dispatchedResult = $dispatchedTaxable->getResult();
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $dispatchedResult);
                        $unit = $dispatchedResult->getUnit();
                        $unit->offsetSet(ResultElement::EXCLUDING_TAX, 20);
                        $dispatchedResult->offsetSet(Result::UNIT, $unit);

                        return true;
                    }
                )
            );

        $result = $this->manager->getTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(20, $result->getUnit()->getExcludingTax());
        $this->assertEquals(null, $result->getUnit()->getIncludingTax());
    }

    public function testGetTaxLoadResult()
    {
        $taxValue = new TaxValue();
        $taxResult = new Result([Result::ROW => new ResultElement([ResultElement::EXCLUDING_TAX => 10])]);
        $taxValue->setResult($taxResult);

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->exactly(2))->method('create')->willReturn($taxable);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn($taxValue);

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->callback(
                    function ($dispatchedTaxable) use ($taxable, $taxResult) {
                        /** @var Taxable $dispatchedTaxable */
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Taxable', $dispatchedTaxable);
                        $this->assertSame($taxable, $dispatchedTaxable);

                        /** @var Result $dispatchedResult */
                        $dispatchedResult = $dispatchedTaxable->getResult();
                        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $dispatchedResult);
                        $this->assertSame($taxResult, $taxResult);
                        /** @var Result $dispatchedResult */
                        $unit = $dispatchedResult->getUnit();
                        $unit->offsetSet(ResultElement::EXCLUDING_TAX, 20);
                        $dispatchedResult->offsetSet(Result::UNIT, $unit);

                        return true;
                    }
                )
            );

        $result = $this->manager->getTax(new \stdClass());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(20, $result->getUnit()->getExcludingTax());
        $this->assertEquals(null, $result->getUnit()->getIncludingTax());
        $this->assertEquals(10, $result->getRow()->getExcludingTax());
        $this->assertEquals(null, $result->getRow()->getIncludingTax());
    }

    public function testSave()
    {
        $entity = new \stdClass();
        $taxValue = new TaxValue();

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->exactly(3))->method('create')->willReturn($taxable);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->getMock('OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('reverseTransform')->willReturnCallback(
            function (Result $result) use ($taxValue) {
                $taxValue->setResult($result);

                return $taxValue;
            }
        );
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn($taxValue);

        $this->taxValueManager->expects($this->once())->method('saveTaxValue')->with($taxValue);

        $this->manager->saveTax($entity);
    }

    public function testSaveNewEntity()
    {
        $entity = new \stdClass();
        $taxValue = new TaxValue();
        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->taxValueManager->expects($this->never())->method('getTaxValue');
        $this->taxValueManager->expects($this->never())->method('saveTaxValue')->with($taxValue);

        $this->manager->saveTax($entity);
    }
}
