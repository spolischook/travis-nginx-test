<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use OroPro\Bundle\ElasticSearchBundle\Engine\IndexAgent;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\ContainsWherePartBuilder;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\EqualsWherePartBuilder;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\InWherePartBuilder;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\Where\RangeWherePartBuilder;
use OroPro\Bundle\ElasticSearchBundle\RequestBuilder\WhereRequestBuilder;

class WhereRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WhereRequestBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $containsPart = new ContainsWherePartBuilder();
        $equalsPart   = new EqualsWherePartBuilder();
        $rangePart    = new RangeWherePartBuilder();
        $inPart       = new InWherePartBuilder($equalsPart);

        $this->builder = new WhereRequestBuilder();

        // part builders are parts of where builder, so they should be tested together
        $this->builder->addPartBuilder($containsPart);
        $this->builder->addPartBuilder($equalsPart);
        $this->builder->addPartBuilder($rangePart);
        $this->builder->addPartBuilder($inPart);
    }

    /**
     * @param array $where
     * @param array $request
     *
     * @dataProvider buildDataProvider
     */
    public function testBuild(array $where, array $request)
    {
        $query = new Query();
        foreach ($where as $part) {
            $query->where($part['keyword'], $part['name'], $part['operator'], $part['value']);
        }

        $this->assertEquals($request, $this->builder->build($query, []));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildDataProvider()
    {
        return [
            'all_text ~ value'                   => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => Indexer::TEXT_ALL_DATA_FIELD,
                        'operator' => Query::OPERATOR_CONTAINS,
                        'value'    => 'value'
                    ]
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'match' => [
                                Indexer::TEXT_ALL_DATA_FIELD . '.' . IndexAgent::FULLTEXT_ANALYZED_FIELD => 'value'
                            ]
                        ]
                    ]
                ],
            ],
            'field ~ value'                      => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_CONTAINS,
                        'value'    => 'value'
                    ]
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'match' => [
                                'field.' . IndexAgent::FULLTEXT_ANALYZED_FIELD => 'value'
                            ]
                        ]
                    ]
                ],
            ],
            'field !~ value'                     => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_NOT_CONTAINS,
                        'value'    => 'value'
                    ]
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must_not' => ['match' => ['field.' . IndexAgent::FULLTEXT_ANALYZED_FIELD => 'value']]
                            ]
                        ]
                    ]
                ],
            ],
            'field1 ~ value1 or field2 ~ value2' => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field1',
                        'operator' => Query::OPERATOR_CONTAINS,
                        'value'    => 'value1'
                    ],
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field2',
                        'operator' => Query::OPERATOR_CONTAINS,
                        'value'    => 'value2'
                    ]
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => [
                                    ['match' => ['field1.' . IndexAgent::FULLTEXT_ANALYZED_FIELD => 'value1']],
                                    ['match' => ['field2.' . IndexAgent::FULLTEXT_ANALYZED_FIELD => 'value2']],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'field = value'                      => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_EQUALS,
                        'value'    => 'value'
                    ]
                ],
                'request' => [
                    'body' => ['query' => ['match' => ['field' => 'value']]]
                ],
            ],
            'field != value'                     => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_NOT_EQUALS,
                        'value'    => 'value'
                    ]
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must_not' => ['match' => ['field' => 'value']]
                            ]
                        ]
                    ]
                ],
            ],
            'field1 = value1 or field2 = value2' => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field1',
                        'operator' => Query::OPERATOR_EQUALS,
                        'value'    => 'value1'
                    ],
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field2',
                        'operator' => Query::OPERATOR_EQUALS,
                        'value'    => 'value2'
                    ]
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => [
                                    ['match' => ['field1' => 'value1']],
                                    ['match' => ['field2' => 'value2']],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'field > 1 and field < 10'           => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_GREATER_THAN,
                        'value'    => 1
                    ],
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_LESS_THAN,
                        'value'    => 10
                    ],
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['range' => ['field' => ['gt' => 1]]],
                                    ['range' => ['field' => ['lt' => 10]]],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'field <= 1 or field => 10'          => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_LESS_THAN_EQUALS,
                        'value'    => 1
                    ],
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_GREATER_THAN_EQUALS,
                        'value'    => 10
                    ],
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => [
                                    ['range' => ['field' => ['lte' => 1]]],
                                    ['range' => ['field' => ['gte' => 10]]],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'field in (first, second)'           => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_IN,
                        'value'    => ['first', 'second']
                    ],
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => [
                                    ['term' => ['field' => 'first']],
                                    ['term' => ['field' => 'second']],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'field in first'                     => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_OR,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_IN,
                        'value'    => ['first']
                    ],
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'should' => [
                                    ['term' => ['field' => 'first']],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'field !in (first, second)'          => [
                'where'   => [
                    [
                        'keyword'  => Query::KEYWORD_AND,
                        'name'     => 'field',
                        'operator' => Query::OPERATOR_NOT_IN,
                        'value'    => ['first', 'second']
                    ],
                ],
                'request' => [
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must_not' => [
                                    ['term' => ['field' => 'first']],
                                    ['term' => ['field' => 'second']],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }
}
