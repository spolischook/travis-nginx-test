<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\OrderRequestBuilder;

class OrderRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $field
     * @param string $direction
     * @param array $request
     * @dataProvider buildDataProvider
     */
    public function testBuild($field, $direction, array $request)
    {
        $query = new Query();
        if ($field) {
            if ($direction) {
                $query->setOrderBy($field, $direction);
            } else {
                $query->setOrderBy($field);
            }
        }

        $builder = new OrderRequestBuilder();

        $this->assertEquals($request, $builder->build($query, []));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            'asc' => [
                'field' => 'name',
                'direction' => Query::ORDER_ASC,
                'request' => [
                    'body' => ['sort' => ['name' => ['order' => Query::ORDER_ASC]]]
                ],
            ],
            'desc' => [
                'field' => 'name',
                'direction' => Query::ORDER_DESC,
                'request' => [
                    'body' => ['sort' => ['name' => ['order' => Query::ORDER_DESC]]]
                ],
            ],
            'no direction' => [
                'field' => 'name',
                'direction' => null,
                'request' => [
                    'body' => ['sort' => ['name' => ['order' => Query::ORDER_ASC]]]
                ],
            ],
            'empty' => [
                'field' => null,
                'direction' => null,
                'request' => [],
            ],
        ];
    }
}
