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

        $this->assertEquals($request, $builder->build($query, array()));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return array(
            'all entities' => array(
                'from' => '*',
                'request' => array(),
            ),
            'one entity' => array(
                'from' => 'first_entity',
                'request' => array('type' => 'first_entity'),
            ),
            'two entities' => array(
                'from' => array('first_entity', 'second_entity'),
                'request' => array('type' => 'first_entity,second_entity'),
            ),
            'no entities' => array(
                'from' => array(),
                'request' => array(),
            )
        );
    }
}
