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

        $this->assertEquals($request, $builder->build($query, array()));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return array(
            'asc' => array(
                'field' => 'name',
                'direction' => Query::ORDER_ASC,
                'request' => array(
                    'body' => array('sort' => array('name' => array('order' => Query::ORDER_ASC)))
                ),
            ),
            'desc' => array(
                'field' => 'name',
                'direction' => Query::ORDER_DESC,
                'request' => array(
                    'body' => array('sort' => array('name' => array('order' => Query::ORDER_DESC)))
                ),
            ),
            'no direction' => array(
                'field' => 'name',
                'direction' => null,
                'request' => array(
                    'body' => array('sort' => array('name' => array('order' => Query::ORDER_ASC)))
                ),
            ),
            'empty' => array(
                'field' => null,
                'direction' => null,
                'request' => array(),
            ),
        );
    }
}
