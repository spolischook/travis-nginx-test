<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\FromRequestBuilder;

class FromRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array|string $from
     * @param array $request
     * @dataProvider buildDataProvider
     */
    public function testBuild($from, array $request)
    {
        $query = new Query();
        $query->from($from);

        $builder = new FromRequestBuilder();

        $this->assertEquals($request, $builder->build($query, []));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            'all entities' => [
                'from' => '*',
                'request' => [],
            ],
            'one entity' => [
                'from' => 'first_entity',
                'request' => ['type' => 'first_entity'],
            ],
            'two entities' => [
                'from' => ['first_entity', 'second_entity'],
                'request' => ['type' => 'first_entity,second_entity'],
            ],
            'no entities' => [
                'from' => [],
                'request' => [],
            ]
        ];
    }
}
