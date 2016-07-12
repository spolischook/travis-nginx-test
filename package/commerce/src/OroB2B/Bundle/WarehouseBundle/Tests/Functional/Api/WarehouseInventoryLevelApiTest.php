<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class WarehouseInventoryLevelApiTest extends RestJsonApiTestCase
{
    const ARRAY_DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                LoadWarehousesAndInventoryLevels::class,
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param string $expectedStatusCode
     * @param array $params
     * @param array $filters
     * @param int $expectedCount
     * @param array $expectedContent
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(
        $entityClass,
        $expectedStatusCode,
        array $params,
        array $filters,
        $expectedCount,
        array $expectedContent = null
    ) {
        $entityType = $this->getEntityType($entityClass);

        foreach ($filters as $filter) {
            $filterValue = '';
            foreach ($filter['references'] as $value) {
                $method = $filter['method'];
                $filterValue .= $this->getReference($value)->$method() . self::ARRAY_DELIMITER;
            }
            $params['filter'][$filter['key']] = substr($filterValue, 0, -1);
        }

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, $expectedStatusCode, $entityType, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);
        if ($expectedContent) {
            $this->assertIsContained($expectedContent, $content['data']);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function cgetParamsAndExpectation()
    {
        return [
            'filter by Product' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1']
                    ],
                ],
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                ],
            ],
            'filter by Products' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                ],
                'expectedCount' => 6,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 42,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'box',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                            'productSku' => 'product.2',
                            'warehouseName' => 'Second Warehouse',
                            'unit' => 'box',
                        ],
                    ],
                ],
            ],
            'filter by Products and Warehouse' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE2]
                    ],
                ],
                'expectedCount' => 1,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                            'productSku' => 'product.2',
                            'warehouseName' => 'Second Warehouse',
                            'unit' => 'box',
                        ],
                    ],
                ],
            ],
            'filter by Products and Unit' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.bottle']
                    ],
                ],
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                ],
            ],
            'filter by Products and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.bottle', 'product_unit.liter']
                    ],
                ],
                'expectedCount' => 4,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouse and Unit' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE1]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter']
                    ],
                ],
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouse and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE1]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter', 'product_unit.bottle']
                    ],
                ],
                'expectedCount' => 4,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouses and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [
                            LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                            LoadWarehousesAndInventoryLevels::WAREHOUSE2
                        ]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter', 'product_unit.bottle', 'product_unit.box']
                    ],
                ],
                'expectedCount' => 5,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                            'productSku' => 'product.1',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'liter',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'bottle',
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 42,
                            'productSku' => 'product.2',
                            'warehouseName' => 'First Warehouse',
                            'unit' => 'box',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $expected
     * @param array $content
     */
    protected function assertIsContained(array $expected, array $content)
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $content);
            if (is_array($value)) {
                $this->assertIsContained($value, $content[$key]);
            } else {
                $this->assertEquals($value, $content[$key]);
            }
        }
    }
}
