<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextTestCase;

class NormalizeRequestDataTest extends FormContextTestCase
{
    /** @var NormalizeRequestData */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    public function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeRequestData($this->valueNormalizer);
    }

    public function testProcessOnValidatedData()
    {
        $data = ['foo' => 'bar'];
        $this->context->setRequestData($data);
        $this->processor->process($this->context);
        $this->assertSame($data, $this->context->getRequestData());
    }

    public function testProcess()
    {
        $inputData = [
            'data' => [
                'attributes'    => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe'
                ],
                'relationships' => [
                    'toOneRelation'       => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation'      => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '3'
                            ]
                        ]
                    ],
                    'emptyToOneRelation'  => ['data' => null],
                    'emptyToManyRelation' => ['data' => []]
                ]
            ]
        ];

        $this->context->setRequestData($inputData);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $requestType, false, 'Test\User'],
                    ['groups', 'entityClass', $requestType, false, 'Test\Groups']
                ]
            );

        $this->processor->process($this->context);

        $expectedData = [
            'firstName'           => 'John',
            'lastName'            => 'Doe',
            'toOneRelation'       => [
                'id'    => '89',
                'class' => 'Test\User'
            ],
            'toManyRelation'      => [
                [
                    'id'    => '1',
                    'class' => 'Test\Groups'
                ],
                [
                    'id'    => '2',
                    'class' => 'Test\Groups'
                ],
                [
                    'id'    => '3',
                    'class' => 'Test\Groups'
                ]
            ],
            'emptyToOneRelation'  => [],
            'emptyToManyRelation' => []
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }
}
