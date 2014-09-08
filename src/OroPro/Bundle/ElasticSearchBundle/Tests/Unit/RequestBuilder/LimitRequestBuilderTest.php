<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\LimitRequestBuilder;

class LimitRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param int|null $firstResult
     * @param int|null $maxResults
     * @param array $request
     * @dataProvider buildDataProvider
     */
    public function testBuild($firstResult, $maxResults, array $request)
    {
        $query = new Query();
        if (null !== $firstResult) {
            $query->setFirstResult($firstResult);
        }

        if (null !== $maxResults) {
            $query->setMaxResults($maxResults);
        }

        $builder = new LimitRequestBuilder();

        $this->assertEquals($request, $builder->build($query, array()));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return array(
            'no data' => array(
                'firstResult' => null,
                'maxResults' => null,
                'request' => array(
                    'body' => array('size' => 0)
                ),
            ),
            'limit' => array(
                'firstResult' => null,
                'maxResults' => 10,
                'request' => array(
                    'body' => array('size' => 10)
                ),
            ),
            'limit and offset' => array(
                'firstResult' => 5,
                'maxResults' => 10,
                'request' => array(
                    'body' => array('from' => 5, 'size' => 10)
                ),
            ),
        );
    }
}
