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

        $this->assertEquals($request, $builder->build($query, []));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            'no data' => [
                'firstResult' => null,
                'maxResults' => null,
                'request' => [
                    'body' => ['size' => 0]
                ],
            ],
            'limit' => [
                'firstResult' => null,
                'maxResults' => 10,
                'request' => [
                    'body' => ['size' => 10]
                ],
            ],
            'limit and offset' => [
                'firstResult' => 5,
                'maxResults' => 10,
                'request' => [
                    'body' => ['from' => 5, 'size' => 10]
                ],
            ],
        ];
    }
}
