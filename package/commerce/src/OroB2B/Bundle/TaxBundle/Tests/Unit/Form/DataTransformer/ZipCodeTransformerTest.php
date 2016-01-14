<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;
use OroB2B\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class ZipCodeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZipCodeTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new ZipCodeTransformer();
    }

    protected function tearDown()
    {
        unset($this->transformer);
    }

    /**
     * @dataProvider testTransformProvider
     * @param ZipCode $zipCode
     * @param string $expected
     */
    public function testTransform($zipCode, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($zipCode));
    }

    /**
     * @return array
     */
    public function testTransformProvider()
    {
        return [
            'nullable value' => [
                'value' => null,
                'expectedCode' => null,
            ],
            'single value zip codes' => [
                'zipCode' => ZipCodeTestHelper::getSingleValueZipCode('01000'),
                'expected' => ZipCodeTestHelper::getRangeZipCode('01000', null),
            ],
            'range zip codes' => [
                'zipCode' => ZipCodeTestHelper::getRangeZipCode('01000', '02000'),
                'expected' => ZipCodeTestHelper::getRangeZipCode('01000', '02000'),
            ],
        ];
    }

    /**
     * @dataProvider testReverseTransformProvider
     * @param ZipCode $value
     * @param ZipCode $expected
     */
    public function testReverseTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));

    }

    /**
     * @return array
     */
    public function testReverseTransformProvider()
    {
        return [
            'nullable value' => [
                'value' => null,
                'expectedCode' => null,
            ],
            'same values in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode('123', '123'),
                'expectedCode' => ZipCodeTestHelper::getSingleValueZipCode('123'),
            ],
            'different values in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode('123', '234'),
                'expectedCode' => ZipCodeTestHelper::getRangeZipCode('123', '234'),
            ],
            'only first value in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode('123', null),
                'expectedCode' => ZipCodeTestHelper::getSingleValueZipCode('123'),
            ],
            'only second value in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode(null, '234'),
                'expectedCode' => ZipCodeTestHelper::getSingleValueZipCode('234'),
            ],
        ];
    }
}
