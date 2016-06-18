<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Update\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var ValidateRequestData */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ValidateRequestData($this->valueNormalizer);
    }

    /**
     * @dataProvider validRequestDataProvider
     */
    public function testProcessWithValidRequestData($requestData)
    {
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');

        $this->context->setId('23');
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }

    public function validRequestDataProvider()
    {
        return [
            [
                ['data' => ['id' => '23', 'type' => 'products', 'attributes' => ['test' => null]]]
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => ['test' => ['data' => null]]]]
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => ['test' => ['data' => []]]]],
            ],
        ];
    }

    /**
     * @dataProvider invalidRequestDataProvider
     */
    public function testProcessWithInvalidRequestData($requestData, $expectedErrorString, $pointer)
    {
        $this->context->setId('23');
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');

        $this->processor->process($this->context);
        $errors = $this->context->getErrors();
        $this->assertCount(1, $errors);
        $expectedError = $errors[0];
        $this->assertEquals($expectedErrorString, $expectedError->getDetail());
        $this->assertEquals($pointer, $expectedError->getSource()->getPointer());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidRequestDataProvider()
    {
        return [
            [[], 'The primary data object should exist', '/data'],
            [['data' => null], 'The primary data object should not be empty', '/data'],
            [['data' => []], 'The primary data object should not be empty', '/data'],
            [['data' => ['attributes' => ['foo' => 'bar']]], 'The \'id\' property is required', '/data/id'],
            [
                ['data' => ['id' => '1', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property is required',
                '/data/type',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property of the primary data object should match the requested resource',
                '/data/type',
            ],
            [
                ['data' => ['id' => '32', 'type' => 'products', 'attributes' => ['foo' => 'bar']]],
                'The \'id\' property of the primary data object should match \'id\' parameter of the query sting',
                '/data/id',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'attributes' => null]],
                'The \'attributes\' property should be an array',
                '/data/attributes',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'attributes' => []]],
                'The \'attributes\' property should not be empty',
                '/data/attributes',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'attributes' => [1, 2, 3]]],
                'The \'attributes\' property should be an associative array',
                '/data/attributes',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => null]],
                'The \'relationships\' property should be an array',
                '/data/relationships',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => []]],
                'The \'relationships\' property should not be empty',
                '/data/relationships',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => [1, 2, 3]]],
                'The \'relationships\' property should be an associative array',
                '/data/relationships',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => ['test' => null]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test',
            ],
            [
                ['data' => ['id' => '23', 'type' => 'products', 'relationships' => ['test' => []]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test',
            ],
            [
                [
                    'data' => [
                        'id'            => '23',
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => ['id' => '2']]]
                    ]
                ],
                'The \'type\' property is required',
                '/data/relationships/test/data/type',
            ],
            [
                [
                    'data' => [
                        'id'            => '23',
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => ['type' => 'products']]]
                    ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/id',
            ],
            [
                [
                    'data' => [
                        'id'            => '23',
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['id' => '2']]]]
                    ]
                ],
                'The \'type\' property is required',
                '/data/relationships/test/data/0/type',
            ],
            [
                [
                    'data' => [
                        'id'            => '23',
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products']]]]
                    ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/0/id',
            ]
        ];
    }
}
